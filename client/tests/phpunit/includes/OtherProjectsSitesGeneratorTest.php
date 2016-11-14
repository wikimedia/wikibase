<?php

namespace Wikibase\Client\Tests;

use HashSiteStore;
use Site;
use Wikibase\Client\OtherProjectsSitesGenerator;

/**
 * @covers Wikibase\Client\OtherProjectsSitesGenerator
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 * @group WikibaseIntegration
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSitesGeneratorTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider otherProjectSitesProvider
	 */
	public function testOtherProjectSiteIds( array $supportedSites, $localSiteId, array $expectedSiteIds ) {
		$siteStore = $this->getSiteStoreMock();
		$otherProjectsSitesProvider = new OtherProjectsSitesGenerator( $siteStore, $localSiteId, [ 'wikidata' ] );

		$this->assertEquals(
			$expectedSiteIds,
			$otherProjectsSitesProvider->getOtherProjectsSiteIds( $supportedSites )
		);
	}

	public function otherProjectSitesProvider() {
		$tests = [];

		$tests['Same language'] = [
			[ 'wikipedia', 'wikisource' ],
			'frwikisource',
			[ 'frwiki' ]
		];

		$tests['Same language + only one in group'] = [
			[ 'wikipedia', 'wikisource', 'commons' ],
			'frwikisource',
			[ 'frwiki', 'commonswiki' ]
		];

		$tests['Only one in group'] = [
			[ 'wikipedia', 'wikisource', 'commons' ],
			'eswiki',
			[ 'commonswiki' ]
		];

		$tests['Special group'] = [
			[ 'wikipedia', 'wikisource', 'special' ],
			'eswiki',
			[ 'wikidatawiki' ]
		];

		$tests['Special group + language'] = [
			[ 'wikipedia', 'wikisource', 'special' ],
			'frwiki',
			[ 'frwikisource', 'wikidatawiki' ]
		];

		$tests['No other sites'] = [
			[ 'wikipedia', 'wikisource' ],
			'eswiki',
			[]
		];

		return $tests;
	}

	public function testOtherProjectSiteIds_unknownSite() {
		$siteStore = $this->getSiteStoreMock();
		$otherProjectsSitesProvider = new OtherProjectsSitesGenerator( $siteStore, 'kittenswiki', [ 'wikidata' ] );

		// getOtherProjectsSiteIds does wfWarn in case it's being called with a siteid
		// it doesn't know about. That's fine, we can just ignore that.
		\MediaWiki\suppressWarnings();
		$result = $otherProjectsSitesProvider->getOtherProjectsSiteIds( [ 'wikipedia', 'wikisource' ] );
		\MediaWiki\restoreWarnings();

		$this->assertSame( [], $result );
	}

	/**
	 * @return HashSiteStore
	 */
	private function getSiteStoreMock() {
		$sites = [];

		$site = new Site();
		$site->setGlobalId( 'foo' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'bar' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'enwiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'frwiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'frwikisource' );
		$site->setGroup( 'wikisource' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'nlwikisource' );
		$site->setGroup( 'wikisource' );
		$site->setLanguageCode( 'nl' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'eswiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'es' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'commonswiki' );
		$site->setGroup( 'commons' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'wikidatawiki' );
		$site->setGroup( 'wikidata' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		return new HashSiteStore( $sites );
	}

}
