<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\MultiplexingEntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Lib\Store\MultiplexingEntityRevisionLookup;
 *
 * @group WikibaseLib
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class MultiplexingEntityRevisionLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|EntityRevisionLookup
	 */
	private function getDummyEntityRevisionLookup() {
		return $this->getMock( EntityRevisionLookup::class );
	}

	public function testGivenExistingRevisionOfLocalEntity_getEntityRevisionReturnsIt() {
		$itemId = new ItemId( 'Q123' );
		$item = new Item( $itemId );

		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->with( $itemId )
			->willReturn( new EntityRevision( $item, 123 ) );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array( '' => $localLookup, ) );

		$revision = $multiplexingLookup->getEntityRevision( new ItemId( 'Q123' ) );

		$this->assertEquals( $item, $revision->getEntity() );
		$this->assertEquals( 123, $revision->getRevisionId() );
	}

	public function testGivenNotExistingLocalEntityId_getEntityRevisionReturnsNull() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->with( new ItemId( 'Q124' ) )
			->willReturn( null );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->assertNull( $multiplexingLookup->getEntityRevision( new ItemId( 'Q124' ) ) );
	}

	public function testGivenExistingLocalEntityId_getLatestRevisionIdReturnsTheId() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getLatestRevisionId' )
			->with( new ItemId( 'Q123' ) )
			->willReturn( 123 );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->assertEquals( 123, $multiplexingLookup->getLatestRevisionId( new ItemId( 'Q123' ) ) );
	}

	public function testGivenNotExistingLocalEntityId_getLatestRevisionIdReturnsFalse() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getLatestRevisionId' )
			->with( new ItemId( 'Q123' ) )
			->willReturn( false );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->assertFalse( $multiplexingLookup->getLatestRevisionId( new ItemId( 'Q123' ) ) );
	}

	public function testLookupExceptionsAreNotCaught() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->willThrowException( new StorageException( 'No such revision for entity Q123: 124' ) );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->setExpectedException( StorageException::class );
		$multiplexingLookup->getEntityRevision( new ItemId( 'Q123' ), 124 );
	}

	public function testGivenExistingRevisionOfForeignEntityFromKnownRepository_getEntityRevisionReturnsIt() {
		$itemId = new ItemId( 'foo:Q123' );
		$item = new Item( $itemId );

		$localLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->with( $itemId )
			->willReturn( new EntityRevision( $item, 123 ) );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array(
			'' => $localLookup,
			'foo' => $fooLookup,
		) );

		$revision = $multiplexingLookup->getEntityRevision( new ItemId( 'foo:Q123' ) );

		$this->assertEquals( $item, $revision->getEntity() );
		$this->assertEquals( 123, $revision->getRevisionId() );
	}

	public function testGivenNotExistingForeignEntityIdFromKnownRepository_getEntityRevisionReturnsNull() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->with( new ItemId( 'foo:Q123' ) )
			->willReturn( null );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array(
			'' => $localLookup,
			'foo' => $fooLookup,
		) );

		$this->assertNull( $multiplexingLookup->getEntityRevision( new ItemId( 'foo:Q123' ) ) );
	}

	public function testGivenExistingForeignEntityIdFromKnownRepository_getLatestRevisionIdReturnsIheId() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup->expects( $this->any() )
			->method( 'getLatestRevisionId' )
			->with( new ItemId( 'foo:Q123' ) )
			->willReturn( 123 );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array(
			'' => $localLookup,
			'foo' => $fooLookup,
		) );

		$this->assertEquals( 123, $multiplexingLookup->getLatestRevisionId( new ItemId( 'foo:Q123' ) ) );
	}

	public function testGivenNotExistingForeignEntityIdFromKnownRepository_getLatestRevisionIdReturnsFalse() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup->expects( $this->any() )
			->method( 'getLatestRevisionId' )
			->with( new ItemId( 'foo:Q123' ) )
			->willReturn( false );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array(
			'' => $localLookup,
			'foo' => $fooLookup,
		) );

		$this->assertFalse( $multiplexingLookup->getLatestRevisionId( new ItemId( 'foo:Q123' ) ) );
	}

	public function testGivenForeignEntityFromUnknownRepository_getEntityRevisionThrowsException() {
		$localLookup = $this->getDummyEntityRevisionLookup();

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->setExpectedException( UnknownForeignRepositoryException::class );
		$multiplexingLookup->getEntityRevision( new ItemId( 'foo:Q123' ) );
	}

	public function testGivenForeignEntityFromUnknownRepository_getLatestRevisionIdThrowsException() {
		$localLookup = $this->getDummyEntityRevisionLookup();

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->setExpectedException( UnknownForeignRepositoryException::class );
		$multiplexingLookup->getLatestRevisionId( new ItemId( 'foo:Q123' ) );
	}

	/**
	 * @dataProvider provideInvalidForeignLookups
	 */
	public function testGivenInvalidForeignLookups_exceptionIsThrown( array $lookups ) {
		$this->setExpectedException( ParameterAssertionException::class );
		new MultiplexingEntityRevisionLookup( $lookups );
	}

	public function provideInvalidForeignLookups() {
		return array(
			'no lookups given' => array( array() ),
			'no lookup given for the local repository' => array(
				array( 'foo' => $this->getDummyEntityRevisionLookup(), )
			),
			'not an implementation of EntityRevisionLookup given as a lookup' => array(
				array( '' => new ItemId( 'Q123' ) ),
			),
			'non-string keys' => array(
				array(
					'' => $this->getDummyEntityRevisionLookup(),
					100 => $this->getDummyEntityRevisionLookup(),
				),
			),
			'repo name containing colon' => array(
				array(
					'' => $this->getDummyEntityRevisionLookup(),
					'fo:oo' => $this->getDummyEntityRevisionLookup(),
				),
			),
		);
	}

}
