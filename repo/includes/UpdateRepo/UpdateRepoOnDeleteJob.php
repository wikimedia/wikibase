<?php

namespace Wikibase\Repo\UpdateRepo;

use Job;
use OutOfBoundsException;
use SiteStore;
use Title;
use User;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\EditEntity;

/**
 * Job for updating the repo after a page on the client has been deleted.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteJob extends Job {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EntityPermissionChecker
	 */
	private $entityPermissionChecker;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * Constructs a UpdateRepoOnMoveJob propagating a page move to the repo
	 *
	 * @note: This is for use by Job::factory, don't call it directly;
	 *           use newFrom*() instead.
	 *
	 * @note: the constructor's signature is dictated by Job::factory, so we'll have to
	 *           live with it even though it's rather ugly for our use case.
	 *
	 * @see Job::factory.
	 *
	 * @param Title $title Ignored
	 * @param array|bool $params
	 */
	public function __construct( Title $title, $params = false ) {
		parent::__construct( 'UpdateRepoOnDelete', $title, $params );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->initServices(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityStore(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityPermissionChecker(),
			$wikibaseRepo->getSiteStore()
		);
	}

	public function initServices(
		EntityTitleLookup $entityTitleLookup,
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		EntityPermissionChecker $entityPermissionChecker,
		SiteStore $siteStore
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityPermissionChecker = $entityPermissionChecker;
		$this->siteStore = $siteStore;
	}

	/**
	 * Get a SiteLink for a specific item and site
	 *
	 * @param Item $item
	 * @param string $globalId
	 *
	 * @return SiteLink|null
	 */
	private function getSiteLink( $item, $globalId ) {
		try {
			return $item->getSiteLink( $globalId );
		} catch( OutOfBoundsException $e ) {
			return null;
		}
	}

	/**
	 * Get a Summary object for the edit
	 *
	 * @param string $globalId Global id of the target site
	 * @param string $page
	 *
	 * @return Summary
	 */
	public function getSummary( $globalId, $page ) {
		return new Summary(
			'clientsitelink',
			'remove',
			null,
			array( $globalId ),
			array( $page )
		);
	}

	/**
	 * Remove the siteLink on the repo to reflect the change in the client
	 *
	 * @param string $siteId Id of the client the change comes from
	 * @param string $itemId
	 * @param string $page
	 * @param User $user User who we'll attribute the remove to
	 *
	 * @return bool Whether something changed
	 */
	public function removeSiteLink( $siteId, $itemId, $page, $user ) {
		wfProfileIn( __METHOD__ );

		$itemId = new ItemId( $itemId );

		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $itemId );
		} catch ( StorageException $ex ) {
			$entityRevision = null;
		}

		if ( $entityRevision === null ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": EntityRevision not found for "
				. $itemId->getSerialization() );

			wfProfileOut( __METHOD__ );
			return false;
		}

		$item = $entityRevision->getEntity();
		$site = $this->siteStore->getSite( $siteId );

		$siteLink = $this->getSiteLink( $item, $siteId );
		if ( !$siteLink || $siteLink->getPageName() !== $page ) {
			// Probably something changed since the job has been inserted
			wfDebugLog( __CLASS__, __FUNCTION__ . ": The site link to $siteId is no longer $page" );
			wfProfileOut( __METHOD__ );
			return false;
		}

		// Maybe the page has been undeleted/ recreated?
		$exists = $site->normalizePageName( $page );
		if ( $exists !== false ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": $page on $siteId exists" );
			wfProfileOut( __METHOD__ );
			return false;
		}

		$summary = $this->getSummary( $siteId, $page );

		return $this->doRemoveSiteLink( $item, $siteLink, $summary, $user );
	}

	/**
	 * Update the given item with the given sitelink
	 *
	 * @param Item $item
	 * @param SiteLink $siteLink
	 * @param Summary $summary
	 * @param User $user User who we'll attribute the update to
	 *
	 * @return bool Whether something changed
	 */
	private function doRemoveSiteLink( Item $item, SiteLink $siteLink, Summary $summary, User $user ) {
		$item->getSiteLinkList()->removeLinkWithSiteId( $siteLink->getSiteId() );

		$editEntity = new EditEntity(
			$this->entityTitleLookup,
			$this->entityRevisionLookup,
			$this->entityStore,
			$this->entityPermissionChecker,
			$item,
			$user,
			true
		);

		$summaryString = $this->summaryFormatter->formatSummary( $summary );

		$status = $editEntity->attemptSave(
			$summaryString,
			EDIT_UPDATE,
			false,
			// Don't (un)watch any pages here, as the user didn't explicitly kick this off
			$this->entityStore->isWatching( $user, $item->getid() )
		);

		if ( !$status->isOK() ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": attemptSave failed: " . $status->getMessage()->text() );
		}

		wfProfileOut( __METHOD__ );

		// TODO: Analyze what happened and let the user know in case a manual fix could be needed
		return $status->isOK();
	}

	/**
	 * Run the job
	 *
	 * @return boolean success
	 */
	public function run() {
		wfProfileIn( __METHOD__ );
		$params = $this->getParams();

		$user = User::newFromName( $params['user'] );
		if ( !$user || !$user->isLoggedIn() ) {
			// This should never happen as we check with CentralAuth
			// that the user actually does exist
			wfLogWarning( 'User ' . $params['user'] . " doesn't exist while CentralAuth pretends it does" );
			wfProfileOut( __METHOD__ );
			return true;
		}

		$this->removeSiteLink(
			$params['siteId'],
			$params['entityId'],
			$params['title'],
			$user
		);

		wfProfileOut( __METHOD__ );
		return true;
	}

}
