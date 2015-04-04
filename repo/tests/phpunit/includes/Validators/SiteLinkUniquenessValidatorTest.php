<?php

namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkCache;
use Wikibase\Test\ChangeOpTestMockProvider;
use Wikibase\Validators\SiteLinkUniquenessValidator;

/**
 * @covers Wikibase\Validators\SiteLinkUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SiteLinkUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return SiteLinkCache
	 */
	private function getMockSiteLinkCache() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockSitelinkCache();
	}

	public function testValidateEntity() {
		$goodEntity = new Item( new ItemId( 'Q5' ) );
		$goodEntity->getSiteLinkList()->addNewSiteLink( 'testwiki', 'Foo' );

		$siteLinkCache = $this->getMockSiteLinkCache();

		$validator = new SiteLinkUniquenessValidator( $siteLinkCache );

		$result = $validator->validateEntity( $goodEntity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	public function testValidateEntity_conflict() {
		$badEntity = new Item( new ItemId( 'Q7' ) );
		$badEntity->getSiteLinkList()->addNewSiteLink( 'testwiki', 'DUPE' );

		$siteLinkCache = $this->getMockSiteLinkCache();

		$validator = new SiteLinkUniquenessValidator( $siteLinkCache );

		$result = $validator->validateEntity( $badEntity );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( 'sitelink-conflict', $errors[0]->getCode() );
		$this->assertInstanceOf( 'Wikibase\Validators\UniquenessViolation', $errors[0] );

		//NOTE: ChangeOpTestMockProvider::getSiteLinkConflictsForItem() uses 'Q666' as
		//      the conflicting item for all site links with the name 'DUPE'.
		$this->assertEquals( 'Q666', $errors[0]->getConflictingEntity() );
	}

}
