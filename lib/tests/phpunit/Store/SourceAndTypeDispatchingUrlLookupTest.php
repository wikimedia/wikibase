<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Store;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\Tests\NewEntitySource;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\SourceAndTypeDispatchingUrlLookup;

/**
 * @covers \Wikibase\Lib\Store\SourceAndTypeDispatchingUrlLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingUrlLookupTest extends TestCase {

	/** @var array */
	private $callbacks;

	/** @var EntitySourceLookup */
	private $entitySourceLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->callbacks = [];
		$this->entitySourceLookup = $this->createStub( EntitySourceLookup::class );
	}

	public function provideGetUrlMethods(): array {
		return [
			[ 'getFullUrl' ],
			[ 'getLinkUrl' ],
		];
	}

	/**
	 * @dataProvider provideGetUrlMethods
	 */
	public function testGivenNoLookupDefinedForEntityType_throwsException( string $method ) {
		$entityId = new PropertyId( 'P123' );

		$this->callbacks['some-other-source']['property'] = $this->newNeverCalledMockLookup();

		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$this->entitySourceLookup->expects( $this->once() )
			->method( 'getEntitySourceById' )
			->with( $entityId )
			->willReturn( NewEntitySource::havingName( 'foo' )->build() );

		$this->expectException( LogicException::class );

		$this->newUrlLookup()->$method( $entityId );
	}

	/**
	 * @dataProvider provideGetUrlMethods
	 */
	public function testGivenLookupDefinedForEntityType_usesRespectiveLookup( string $method ) {
		$entityId = new PropertyId( 'P321' );
		$url = 'http://some-wikibase/wiki/Property:P321';
		$sourceName = 'wikidorta';

		$this->callbacks['some-other-source']['property'] = $this->newNeverCalledMockLookup();
		$this->callbacks[$sourceName]['property'] = function () use ( $entityId, $url, $method ) {
			$propertyUrlLookup = $this->createMock( EntityUrlLookup::class );
			$propertyUrlLookup->expects( $this->once() )
				->method( $method )
				->with( $entityId )
				->willReturn( $url );

			return $propertyUrlLookup;
		};
		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$this->entitySourceLookup->expects( $this->once() )
			->method( 'getEntitySourceById' )
			->with( $entityId )
			->willReturn( NewEntitySource::havingName( $sourceName )->build() );

		$this->assertSame( $url, $this->newUrlLookup()->$method( $entityId ) );
	}

	private function newNeverCalledMockLookup(): EntityUrlLookup {
		$lookup = $this->createMock( EntityUrlLookup::class );
		$lookup->expects( $this->never() )->method( $this->anything() );

		return $lookup;
	}

	private function newUrlLookup(): SourceAndTypeDispatchingUrlLookup {
		return new SourceAndTypeDispatchingUrlLookup(
			new ServiceBySourceAndTypeDispatcher(
				EntityUrlLookup::class,
				$this->callbacks
			),
			$this->entitySourceLookup
		);
	}

}
