<?php

namespace Wikibase\Repo\Specials;

use Html;
use InvalidArgumentException;
use SiteStore;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRedirectLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\DataModel\Services\EntityId\EntityIdParsingException;
use Wikibase\DataModel\Services\EntityId\EntityIdParser;

/**
 * Enables accessing a linked page on a site by providing the item id and site
 * id.
 *
 * @licence GNU GPL v2+
 * @author Jan Zerebecki
 */
class SpecialGoToLinkedPage extends SpecialWikibasePage {

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityRedirectLookup
	 */
	private $redirectLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * Error message key
	 * @var string|null
	 */
	private $errorMessageKey;

	/**
	 * @see SpecialWikibasePage::__construct
	 */
	public function __construct() {
		parent::__construct( 'GoToLinkedPage', '', true );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->getStore()->newSiteLinkStore(),
			$wikibaseRepo->getStore()->getEntityRedirectLookup(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStore()->getEntityLookup()
		);

		$this->errorMessageKey = null;
	}

	/**
	 * Initialize the services used be this special page.
	 * May be used to inject mock services for testing.
	 *
	 * @param SiteStore $siteStore
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityRedirectLookup $redirectLookup
	 */
	public function initServices(
		SiteStore $siteStore,
		SiteLinkLookup $siteLinkLookup,
		EntityRedirectLookup $redirectLookup,
		EntityIdParser $idParser,
		EntityLookup $entityLookup
	) {
		$this->siteStore = $siteStore;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->redirectLookup = $redirectLookup;
		$this->idParser = $idParser;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param string $subPage
	 * @return array array( string $site, string $itemString )
	 */
	protected function getArguments( $subPage ) {
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );
		$site = trim( $request->getVal( 'site', isset( $parts[0] ) ? $parts[0] : '' ) );
		$itemString = trim( $request->getVal( 'itemid', isset( $parts[1] ) ? $parts[1] : 0 ) );

		return array( $site, $itemString );
	}

	/**
	 * @param string $site
	 * @param string|null $itemString
	 * @return string|null the URL to redirect to or null if the sitelink does not exist
	 */
	protected function getTargetUrl( $site, $itemString = null ) {
		$itemId = $this->getItemId( $itemString );

		if ( $site === '' || $itemId === null ) {
			return null;
		}

		$site = $this->stringNormalizer->trimToNFC( $site );

		if ( !$this->siteStore->getSite( $site ) ) {
			// HACK: If the site ID isn't known, add "wiki" to it; this allows the wikipedia
			// subdomains to be used to refer to wikipedias, instead of requiring their
			// full global id to be used.
			// @todo: Ideally, if the site can't be looked up by global ID, we
			// should try to look it up by local navigation ID.
			// Support for this depends on bug T50934.
			$site .= 'wiki';
		}

		$links = $this->loadLinks( $site, $itemId );

		if ( isset( $links[0] ) ) {
			list( , $pageName, ) = $links[0];
			$siteObj = $this->siteStore->getSite( $site );
			$url = $siteObj->getPageUrl( $pageName );
			return $url;
		} else {
			$this->errorMessageKey = "page-not-found";
		}

		return null;
	}

	/**
	 * Parses a string to itemId
	 *
	 * @param string $itemString
	 * @return ItemId|null
	 */
	private function getItemId( $itemString ) {
		try {
			$itemId = $this->idParser->parse( $itemString );
			if ( $itemId instanceof ItemId ) {
				if ( !$this->entityLookup->hasEntity( $itemId ) ) {
					$this->errorMessageKey = "item-not-found";
					return null;
				}
				return $itemId;
			}
		} catch ( EntityIdParsingException $e ) {
			$this->errorMessageKey = 'item-id-invalid';
		} catch ( InvalidArgumentException $e ) {
			$this->errorMessageKey = 'item-id-invalid';
		}

		return null;
	}

	/**
	 * Load the sitelink using a SiteLinkLookup. Resolves item redirects, if needed.
	 *
	 * @param string $site
	 * @param ItemId $itemId
	 *
	 * @return array[]
	 */
	private function loadLinks( $site, ItemId $itemId ) {
		$links = $this->siteLinkLookup->getLinks( array( $itemId->getNumericId() ), array( $site ) );
		if ( isset( $links[0] ) ) {
			return $links;
		}

		// Maybe the item is a redirect: Try to resolve the redirect and load
		// the links from there.
		$redirectTarget = $this->redirectLookup->getRedirectForEntityId( $itemId );

		if ( $redirectTarget instanceof ItemId ) {
			return $this->siteLinkLookup->getLinks( array( $redirectTarget->getNumericId() ), array( $site ) );
		}

		return array();
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		list( $site, $itemString ) = $this->getArguments( $subPage );

		if ( !empty( $site ) || !empty( $itemString ) ) {
			$url = $this->getTargetUrl( $site, $itemString );
			if ( null !== $url ) {
				return $this->getOutput()->redirect( $url );
			}
		}

		$this->outputError();
		$this->outputForm( $site, $itemString );
	}

	/**
	 * Output a form via the context's OutputPage object to go to a
	 * sitelink (linked page) for an item and site id.
	 *
	 * @param string $site
	 * @param string $itemString
	 */
	protected function outputForm( $site, $itemString ) {
		$this->getOutput()->addModuleStyles( 'wikibase.special' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getPageTitle()->getFullUrl(),
					'name' => 'gotolinkedpage',
					'id' => 'wb-gotolinkedpage-form1'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-gotolinkedpage-lookup-fieldset' )->text()
			)
			. Html::label(
				$this->msg( 'wikibase-gotolinkedpage-lookup-site' )->text(),
				'wb-gotolinkedpage-sitename',
				array(
					'class' => 'wb-label'
				)
			)
			. Html::input(
				'site',
				$site ? htmlspecialchars( $site ) : '',
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-gotolinkedpage-sitename',
					'size' => 12
				)
			)
			. ' '
			. Html::label(
				$this->msg( 'wikibase-gotolinkedpage-lookup-item' )->text(),
				'wb-gotolinkedpage-itemid',
				array(
					'class' => 'wb-label'
				)
			)
			. Html::input(
				'itemid',
				$itemString ? htmlspecialchars( $itemString ) : '',
				'text',
				array(
					'id' => 'wb-gotolinkedpage-itemid',
					'size' => 36,
					'class' => 'wb-input'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-gotolinkedpage-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-gotolinkedpage-submit',
					'class' => 'wb-button'
				)
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

	/**
	 * Outputs an error message
	 */
	private function outputError() {
		if ( $this->errorMessageKey !== null ) {
			$this->showErrorHTML(
				$this->msg( 'wikibase-gotolinkedpage-error-' . $this->errorMessageKey ) );
		}
	}

}
