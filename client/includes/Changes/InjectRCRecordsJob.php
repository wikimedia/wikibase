<?php

namespace Wikibase\Client\Changes;

use InvalidArgumentException;
use Job;
use JobSpecification;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Title;
use TitleFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesFinder;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikimedia\Assert\Assert;

/**
 * Job for injecting RecentChange records representing changes on the Wikibase repository.
 *
 * @see @ref md_docs_topics_change-propagation for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class InjectRCRecordsJob extends Job {

	/**
	 * @var ClientDomainDb
	 */
	private $db;

	/**
	 * @var EntityChangeLookup
	 */
	private $changeLookup;

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var RecentChangeFactory
	 */
	private $rcFactory;

	/**
	 * @var RecentChangesFinder|null
	 */
	private $rcDuplicateDetector = null;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var StatsdDataFactoryInterface|null
	 */
	private $stats = null;

	/**
	 * @param Title[] $titles
	 * @param EntityChange $change
	 * @param array $rootJobParams
	 *
	 * @return JobSpecification
	 */
	public static function makeJobSpecification(
		array $titles,
		EntityChange $change,
		array $rootJobParams = []
	) {
		$pages = [];

		foreach ( $titles as $t ) {
			$id = $t->getArticleID();
			$pages[$id] = [ $t->getNamespace(), $t->getDBkey() ];
		}

		// Note: Avoid serializing Change objects. Original changes can be restored
		// from $changeData['info']['change-ids'], see getChange().
		$changeData = $change->getFields();
		$changeData['info'] = $change->getSerializedInfo( [ 'changes' ] );

		// See JobQueueChangeNotificationSender::getJobSpecification for relevant root job parameters.
		$params = array_merge( $rootJobParams, [
			'change' => $changeData,
			'pages' => $pages
		] );

		return new JobSpecification(
			'wikibase-InjectRCRecords',
			$params
		);
	}

	/**
	 * Constructs an InjectRCRecordsJob for injecting a change into the recentchanges feed
	 * for the given pages.
	 *
	 * @param ClientDomainDb $domainDb
	 * @param EntityChangeLookup $changeLookup
	 * @param EntityChangeFactory $changeFactory
	 * @param RecentChangeFactory $rcFactory
	 * @param TitleFactory $titleFactory
	 * @param array $params Needs to have two keys: "change": the id of the change,
	 *     "pages": array of pages, represented as $pageId => [ $namespace, $dbKey ].
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		ClientDomainDb $domainDb,
		EntityChangeLookup $changeLookup,
		EntityChangeFactory $changeFactory,
		RecentChangeFactory $rcFactory,
		TitleFactory $titleFactory,
		array $params
	) {
		$title = Title::makeTitle( NS_SPECIAL, 'Badtitle/' . __CLASS__ );
		parent::__construct( 'wikibase-InjectRCRecords', $title, $params );

		Assert::parameter(
			isset( $params['change'] ),
			'$params',
			'$params[\'change\'] not set.'
		);
		// TODO: disallow integer once T172394 has been deployed and old jobs have cleared the queue.
		Assert::parameterType(
			'integer|array',
			$params['change'],
			'$params[\'change\']'
		);

		Assert::parameter(
			isset( $params['pages'] ),
			'$params',
			'$params[\'pages\'] not set.'
		);
		Assert::parameterElementType(
			'array',
			$params['pages'],
			'$params[\'pages\']'
		);

		$this->db = $domainDb;
		$this->changeLookup = $changeLookup;
		$this->changeFactory = $changeFactory;
		$this->rcFactory = $rcFactory;
		$this->titleFactory = $titleFactory;
		$this->logger = new NullLogger();
	}

	public static function newFromGlobalState( Title $unused, array $params ) {
		$mwServices = MediaWikiServices::getInstance();

		$store = WikibaseClient::getStore( $mwServices );

		$job = new self(
			WikibaseClient::getClientDomainDbFactory( $mwServices )->newLocalDb(),
			WikibaseClient::getEntityChangeLookup( $mwServices ),
			WikibaseClient::getEntityChangeFactory( $mwServices ),
			WikibaseClient::getRecentChangeFactory( $mwServices ),
			$mwServices->getTitleFactory(),
			$params
		);

		$job->setRecentChangesFinder( $store->getRecentChangesFinder() );

		$job->setLogger( WikibaseClient::getLogger( $mwServices ) );
		$job->setStats( $mwServices->getStatsdDataFactory() );

		return $job;
	}

	public function setRecentChangesFinder( RecentChangesFinder $rcDuplicateDetector ) {
		$this->rcDuplicateDetector = $rcDuplicateDetector;
	}

	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	public function setStats( StatsdDataFactoryInterface $stats ) {
		$this->stats = $stats;
	}

	/**
	 * Returns the change that should be processed.
	 *
	 * EntityChange objects are loaded using a EntityChangeLookup.
	 *
	 * @return EntityChange|null the change to process (or none).
	 */
	private function getChange() {
		$params = $this->getParams();
		$changeData = $params['change'];

		if ( is_int( $changeData ) ) {
			// TODO: this can be removed once T172394 has been deployed
			//       and old jobs have cleared the queue.
			$this->logger->debug( __FUNCTION__ . ": loading change $changeData." );

			$changes = $this->changeLookup->loadByChangeIds( [ $changeData ] );

			$change = reset( $changes );

			if ( !$change ) {
				$this->logger->error( __FUNCTION__ . ": failed to load change $changeData." );
			}
		} else {
			$change = $this->changeFactory->newFromFieldData( $params['change'] );

			// If the current change was composed of other child changes, restore the
			// child objects.
			$info = $change->getInfo();
			if ( isset( $info['change-ids'] ) && !isset( $info['changes'] ) ) {
				$children = $this->changeLookup->loadByChangeIds( $info['change-ids'] );
				$info['changes'] = $children;
				$change->setField( 'info', $info );
			}
		}

		return $change;
	}

	/**
	 * @return Title[] List of Titles to inject RC entries for, indexed by page ID
	 */
	private function getTitles() {
		$params = $this->getParams();
		$pages = $params['pages'];

		$titles = [];

		foreach ( $pages as $pageId => list( $namespace, $dbKey ) ) {
			$titles[$pageId] = $this->titleFactory->makeTitle( $namespace, $dbKey );
		}

		return $titles;
	}

	/**
	 * @return bool success
	 */
	public function run() {
		$change = $this->getChange();
		$titles = $this->getTitles();

		if ( !$change || $titles === [] ) {
			return false;
		}

		$rcAttribs = $this->rcFactory->prepareChangeAttributes( $change );

		$dbw = $this->db->connections()->getWriteConnection();

		$dbw->startAtomic( __METHOD__ );

		foreach ( $titles as $title ) {
			if ( !$title->exists() ) {
				continue;
			}

			$rc = $this->rcFactory->newRecentChange( $change, $title, $rcAttribs );

			if ( $this->rcDuplicateDetector
				&& $this->rcDuplicateDetector->getRecentChangeId( $rc ) !== null
			) {
				$this->logger->debug( __FUNCTION__ . ": skipping duplicate RC entry for " . $title->getFullText() );
			} else {
				$this->logger->debug( __FUNCTION__ . ": saving RC entry for " . $title->getFullText() );
				$rc->save();
			}
		}

		$dbw->endAtomic( __METHOD__ );
		$this->incrementStats( 'InjectRCRecords.run.titles', count( $titles ) );

		$this->db->connections()->releaseConnection( $dbw );

		return true;
	}

	/**
	 * @param string $updateType
	 * @param int $delta
	 */
	private function incrementStats( $updateType, $delta ) {
		if ( $this->stats ) {
			$this->stats->updateCount( 'wikibase.client.pageupdates.' . $updateType, $delta );
		}
	}

}
