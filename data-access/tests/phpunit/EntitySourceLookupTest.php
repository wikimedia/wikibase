<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \Wikibase\DataAccess\EntitySourceLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceLookupTest extends TestCase {

	public function testGivenUriEntityId_returnsEntitySourceWithMatchingConceptUri() {
		$expectedSource = NewEntitySource::create()
			->withConceptBaseUri( 'http://wikidata.org/entity/' )
			->withType( EntitySource::TYPE_API )
			->build();
		$entityId = new FederatedPropertyId( 'http://wikidata.org/entity/P123' );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ), new SubEntityTypesMapper( [] ) );

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $entityId ) );
	}

	public function testGivenUnprefixedEntityId_returnsDbEntitySourceForEntityType() {
		$id = new PropertyId( 'P123' );
		$expectedSource = NewEntitySource::havingName( 'im a db source!' )
			->withEntityNamespaceIdsAndSlots( [ 'property' => [ 'namespaceId' => 121, 'slot' => 'main' ] ] )
			->build();

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ), new SubEntityTypesMapper( [] ) );

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $id ) );
	}

	public function testGivenEntityIdWithNoMatchingSource_throwsException() {
		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewEntitySource::havingName( 'im a property source' )
				->withEntityNamespaceIdsAndSlots( [ 'property' => [ 'namespaceId' => 121, 'slot' => 'main' ] ] )
				->build(),
		] ), new SubEntityTypesMapper( [] ) );

		$this->expectException( LogicException::class );

		$lookup->getEntitySourceById( new ItemId( 'Q666' ) );
	}

	public function testGivenUriEntityId_WithMatchingConceptUri_ButWithDBEntitySource_throws() {
		$expectedSource = NewEntitySource::havingName( 'expected source' )
			->withConceptBaseUri( 'http://wikidata.org/entity/' )
			->build();
		$entityId = new FederatedPropertyId( 'http://wikidata.org/entity/P123' );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ), new SubEntityTypesMapper( [] ) );

		$this->expectException( LogicException::class );
		$lookup->getEntitySourceById( $entityId );
	}

	public function testGivenSubEntityId_returnsParentEntitySource() {
		$subEntityId = $this->createStub( EntityId::class );
		$subEntityId->method( 'getSerialization' )->willReturn( 'L123-F123' );
		$subEntityId->method( 'getEntityType' )->willReturn( 'form' );

		$expectedSource = NewEntitySource::havingName( 'lexeme source' )
			->withEntityNamespaceIdsAndSlots( [ 'lexeme' => [ 'namespaceId' => 121, 'slot' => 'main' ] ] )
			->build();

		$lookup = new EntitySourceLookup(
			$this->newEntitySourceDefinitionsFromSources( [
				NewEntitySource::havingName( 'some other source' )->build(),
				$expectedSource,
			] ),
			new SubEntityTypesMapper( [ 'lexeme' => [ 'form', 'sense' ] ] )
		);

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $subEntityId ) );
	}

	private function newEntitySourceDefinitionsFromSources( array $sources ): EntitySourceDefinitions {
		return new EntitySourceDefinitions( $sources, new SubEntityTypesMapper( [] ) );
	}

}
