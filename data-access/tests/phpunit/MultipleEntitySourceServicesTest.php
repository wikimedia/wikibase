<?php

namespace Wikibase\DataAccess\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;

/**
 * @covers \Wikibase\DataAccess\MultipleEntitySourceServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultipleEntitySourceServicesTest extends TestCase {

	public function testGetEntityRevisionLookupReturnsLookupThatReturnsExpectedRevisionData() {
		$itemRevisionData = 'item revision data';
		$itemRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$itemRevisionLookup->method( 'getEntityRevision' )
			->willReturn( $itemRevisionData );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getEntityRevisionLookup' )
			->willReturn( $itemRevisionLookup );

		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->method( 'getEntityRevisionLookup' )
			->willReturn( $this->newThrowingEntityRevisionLookup() );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$lookup = $services->getEntityRevisionLookup();

		$this->assertSame( $itemRevisionData, $lookup->getEntityRevision( new ItemId( 'Q123' ) ) );
	}

	private function newThrowingEntityRevisionLookup() {
		$propertyRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$propertyRevisionLookup->method( $this->anything() )
			->willThrowException( new LogicException( 'This service should not be used' ) );
		return $propertyRevisionLookup;
	}

	public function testGetEntityPrefetcherReturnsServiceBufferingDataOfSourceEntities() {
		$itemId = new ItemId( 'Q200' );
		$propertyId = new PropertyId( 'P500' );

		$itemPrefetcher = new EntityPrefetcherSpy();

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getEntityPrefetcher' )
			->willReturn( $itemPrefetcher );

		$propertyPrefetcher = new EntityPrefetcherSpy();

		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->method( 'getEntityPrefetcher' )
			->willReturn( $propertyPrefetcher );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$prefetcher = $services->getEntityPrefetcher();
		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$this->assertEquals(
			[ $itemId, $propertyId ],
			array_merge( $itemPrefetcher->getPrefetchedEntities(), $propertyPrefetcher->getPrefetchedEntities() )
		);
	}

	public function testGetEntityPrefetcherReturnsServiceThatDoesNotPrefetchEntitiesNotConfiguredInSources() {
		$itemId = new ItemId( 'Q200' );
		$propertyId = new PropertyId( 'P500' );

		$itemPrefetcher = new EntityPrefetcherSpy();

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getEntityPrefetcher' )
			->willReturn( $itemPrefetcher );

		$entityTypeDefinitions = new EntityTypeDefinitions( [] );

		$services = new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource(
					'items',
					'itemdb',
					[ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ],
					'',
					'',
					'',
					''
				)
			], $entityTypeDefinitions ),
			[ 'items' => $itemServices ]
		);

		$prefetcher = $services->getEntityPrefetcher();
		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$this->assertNotContains( [ $propertyId ], $itemPrefetcher->getPrefetchedEntities() );
	}

	public function testGetPropertyInfoLookupReturnsPropertyDataAccessingService() {
		$propertyId = new PropertyId( 'P6' );

		$propertyLookup = new MockPropertyInfoLookup();
		$propertyLookup->addPropertyInfo( $propertyId, [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );

		$sourceServices = $this->createMock( SingleEntitySourceServices::class );
		$sourceServices->method( 'getPropertyInfoLookup' )
			->willReturn( $propertyLookup );

		$services = $this->newMultipleEntitySourceServices( [ 'props' => $sourceServices ] );

		$lookup = $services->getPropertyInfoLookup();

		$this->assertEquals( [ 'type' => 'string' ], $lookup->getPropertyInfo( $propertyId ) );
	}

	public function testGivenNoSourceProvidingProperties_getPropertyInfoLookupThrowsException() {
		$entityTypeDefinitions = new EntityTypeDefinitions( [] );
		$services = new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource(
					'items',
					'itemdb',
					[ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ],
					'',
					'',
					'',
					''
				),
			], $entityTypeDefinitions ),
			[]
		);

		$this->expectException( LogicException::class );
		$services->getPropertyInfoLookup();
	}

	public function testEntityFromKnownSourceUpdated_entityUpdatedPassedToRelevantServiceContainer() {
		$itemRevision = new EntityRevision( new Item( new ItemId( 'Q1' ) ) );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'entityUpdated' )
			->with( $itemRevision );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'entityUpdated' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->entityUpdated( $itemRevision );
	}

	public function testEntityFromKnownSourceDeleted_entityDeletedPassedToRelevantServiceContainer() {
		$itemId = new ItemId( 'Q1' );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'entityDeleted' )
			->with( $itemId );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'entityDeleted' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->entityDeleted( $itemId );
	}

	public function testRedirectOfEntityFromKnownSourceDeleted_redirectUpdatedPassedToRelevantServiceContainer() {
		$itemRedirect = new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q300' ) );
		$revisionId = 333;

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'redirectUpdated' )
			->with( $itemRedirect, $revisionId );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'redirectUpdated' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->redirectUpdated( $itemRedirect, $revisionId );
	}

	/**
	 * @param SingleEntitySourceServices[] $perSourceServices
	 * @return MultipleEntitySourceServices
	 */
	private function newMultipleEntitySourceServices( array $perSourceServices ) {
		$entityTypeDefinitions = new EntityTypeDefinitions( [] );
		return new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource(
					'items',
					'itemdb',
					[ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ],
					'',
					'',
					'',
					''
				),
				new EntitySource(
					'props',
					'propb',
					[ 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ],
					'',
					'prop',
					'prop',
					'props'
				),
			], $entityTypeDefinitions ),
			$perSourceServices
		);
	}

}
