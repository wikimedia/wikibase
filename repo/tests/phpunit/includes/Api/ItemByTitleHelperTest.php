<?php

namespace Wikibase\Test\Repo\Api;

use MediaWikiSite;
use SiteStore;
use Title;
use UsageException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\Api\ItemByTitleHelper;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\Api\ItemByTitleHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 * @author Addshore
 */
class ItemByTitleHelperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return SiteStore
	 */
	public function getSiteStoreMock() {
		$dummySite = new MediaWikiSite();

		$siteStoreMock = $this->getMockBuilder( SiteStore::class )
			->disableOriginalConstructor()
			->getMock();

		$siteStoreMock->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnValue( $dummySite ) );

		return $siteStoreMock;
	}

	/**
	 * Gets a mock ResultBuilder object which excepts a certain number of calls to certain methods
	 *
	 * @param int $expectedNormalizedTitle number of expected call to this method
	 * @return ResultBuilder
	 */
	public function getResultBuilderMock( $expectedNormalizedTitle = 0 ) {
		$apiResultBuilderMock = $this->getMockBuilder( ResultBuilder::class )
			->disableOriginalConstructor()
			->getMock();
		$apiResultBuilderMock->expects( $this->exactly( $expectedNormalizedTitle ) )
			->method( 'addNormalizedTitle' );

		return $apiResultBuilderMock;
	}

	/**
	 * @param mixed $itemId
	 *
	 * @return SiteLinkLookup
	 */
	private function getSiteLinkLookupMock( $itemId ) {
		$siteLinkLookupMock = $this->getMockBuilder( SiteLinkLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkLookupMock->expects( $this->any() )
			->method( 'getItemIdForLink' )
				->will( $this->returnValue( $itemId ) );

		return $siteLinkLookupMock;
	}

	public function testGetEntityIdsSuccess() {
		$expectedEntityId = new ItemId( 'Q123' );
		$expectedEntityId = $expectedEntityId->getSerialization();

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getResultBuilderMock(),
			$this->getSiteLinkLookupMock( new ItemId( 'Q123' ) ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$sites = [ 'FooSite' ];
		$titles = [ 'Berlin', 'London' ];

		list( $entityIds, ) = $itemByTitleHelper->getItemIds( $sites, $titles, false );

		foreach ( $entityIds as $entityId ) {
			$this->assertEquals( $expectedEntityId, $entityId );
		}
	}

	/**
	 * Try to get an entity id for a page that's normalized with normalization.
	 */
	public function testGetEntityIdNormalized() {
		$itemByTitleHelper = new ItemByTitleHelper(
		// Two values should be added: The normalization and the failure to find an entity
			$this->getResultBuilderMock( 1 ),
			$this->getSiteLinkLookupMock( null ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$sites = [ 'FooSite' ];
		$titles = [ 'berlin_germany' ];

		list( $entityIds, ) = $itemByTitleHelper->getItemIds( $sites, $titles, true );

		// Still nothing could be found
		$this->assertEquals( [], $entityIds );
	}

	/**
	 * Tries to get entity ids for two pages which don't exist.
	 * Makes sure that the failures are added to the API result.
	 */
	public function testGetEntityIdsNotFound() {
		$itemByTitleHelper = new ItemByTitleHelper(
		// Two result values should be added (for both titles which wont be found)
			$this->getResultBuilderMock(),
			$this->getSiteLinkLookupMock( false ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$sites = [ 'FooSite' ];
		$titles = [ 'Berlin', 'London' ];

		$itemByTitleHelper->getItemIds( $sites, $titles, false );
	}

	/**
	 * Makes sure the request will fail if we want normalization for two titles
	 */
	public function testGetEntityIdsNormalizationNotAllowed() {
		$this->setExpectedException( UsageException::class );

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getResultBuilderMock(),
			$this->getSiteLinkLookupMock( 1 ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$sites = [ 'FooSite' ];
		$titles = [ 'Berlin', 'London' ];

		$itemByTitleHelper->getItemIds( $sites, $titles, true );
	}

	public function normalizeTitleProvider() {
		return [
			[
				'foo_bar',
				123,
				// The normalization should be noted
				1
			],
			[
				'Bar',
				false,
				// Already normalized
				0
			],
		];
	}

	/**
	 * @dataProvider normalizeTitleProvider
	 */
	public function testNormalizeTitle( $title, $expectedEntityId, $expectedAddNormalizedCalls ) {
		$dummySite = new MediaWikiSite();

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getResultBuilderMock( $expectedAddNormalizedCalls ),
			$this->getSiteLinkLookupMock( $expectedEntityId ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$itemByTitleHelper->normalizeTitle( $title, $dummySite );

		// Normalization in unit tests is actually using Title::getPrefixedText instead of a real API call
		// XXX: The Normalized title is passed by via reference to $title...
		$this->assertEquals( Title::newFromText( $title )->getPrefixedText(), $title );
	}

	public function notEnoughInputProvider() {
		return [
			[
				// Request with no sites
				[],
				[ 'barfoo' ],
				false
			],
			[
				// Request with no titles
				[ 'enwiki' ],
				[],
				false
			],
		];
	}

	/**
	 * @dataProvider notEnoughInputProvider
	 */
	public function testNotEnoughInput( array $sites, array $titles, $normalize ) {
		$this->setExpectedException( UsageException::class );

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getResultBuilderMock(),
			$this->getSiteLinkLookupMock( new ItemId( 'Q123' ) ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$itemByTitleHelper->getItemIds( $sites, $titles, $normalize );
	}

}
