<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\NewEntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityExistenceCheckerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {

		$itemId = new ItemId( 'Q123' );
		$sources = [
			NewEntitySource::havingName( 'itemSource' )
				->withEntityNamespaceIdsAndSlots( [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ] )
				->withConceptBaseUri( 'http://wikidorta.org/schmentity/' )
				->build()
		];

		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( $sources, new SubEntityTypesMapper( [] ) )
		);

		$this->mockService( 'WikibaseRepo.EntitySourceAndTypeDefinitions',
			new EntitySourceAndTypeDefinitions(
				[
					EntitySource::TYPE_DB => new EntityTypeDefinitions( [
						Item::ENTITY_TYPE => [
							EntityTypeDefinitions::EXISTENCE_CHECKER_CALLBACK => function () use ( $itemId ) {
								$entityExistenceChecker = $this->createMock( EntityExistenceChecker::class );
								$entityExistenceChecker->expects( $this->once() )
									->method( 'exists' )
									->with( $itemId )
									->willReturn( true );
								return $entityExistenceChecker;
							},
						],
					] ),
				],
				$sources
			)
		);
		$this->mockService(
			'WikibaseRepo.SubEntityTypesMapper',
			new SubEntityTypesMapper( [] )
		);

		/** @var EntityExistenceChecker $entityExistenceChecker */
		$entityExistenceChecker = $this->getService( 'WikibaseRepo.EntityExistenceChecker' );

		$this->assertInstanceOf( EntityExistenceChecker::class, $entityExistenceChecker );
		$this->assertTrue( $entityExistenceChecker->exists( $itemId ) );
	}

}
