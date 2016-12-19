<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * @covers Wikibase\Test\MockPropertyInfoLookup
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibasePropertyInfo
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MockPropertyInfoLookupTest extends \MediaWikiTestCase {

	private function newMockPropertyInfoLookup() {
		return new MockPropertyInfoLookup( [
			'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			'P42' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
		] );
	}

	public function testGivenKnownPropertyId_getPropertyInfoReturnsTheInfo() {
		$lookup = $this->newMockPropertyInfoLookup();

		$this->assertSame(
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			$lookup->getPropertyInfo( new PropertyId( 'P23' ) )
		);
	}

	public function testGivenUnknownPropertyId_getPropertyInfoReturnsNull() {
		$lookup = $this->newMockPropertyInfoLookup();

		$this->assertNull( $lookup->getPropertyInfo( new PropertyId( 'P32' ) ) );
	}

	public function testGetAllPropertyInfo() {
		$lookup = $this->newMockPropertyInfoLookup();

		$this->assertSame(
			[
				'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
				'P42' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
			],
			$lookup->getAllPropertyInfo()
		);
	}

	public function testGivenDataTypeWithKnownProperties_getPropertyInfoForDataTypeReturnsTheInfo() {
		$lookup = $this->newMockPropertyInfoLookup();

		$this->assertSame(
			[
				'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
				'P42' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
			],
			$lookup->getPropertyInfoForDataType( 'string' )
		);
	}

	public function testGivenDataTypeWithNoProperties_getPropertyInfoForDataTypeReturnsEmptyList() {
		$lookup = $this->newMockPropertyInfoLookup();

		$this->assertSame(
			[],
			$lookup->getPropertyInfoForDataType( 'external-id' )
		);
	}

	public function testAddPropertyInfo() {
		$lookup = $this->newMockPropertyInfoLookup();
		$propertyId = new PropertyId( 'P234' );

		$this->assertNull( $lookup->getPropertyInfo( $propertyId ) );

		$lookup->addPropertyInfo( $propertyId, [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );

		$this->assertSame(
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			$lookup->getPropertyInfo( $propertyId )
		);
	}

}
