<?php

namespace Wikibase\Test;

use Wikibase\Change;
use Wikibase\ChunkAccess;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeDispatcher;
use Wikibase\Repo\Notifications\ChangeNotificationSender;
use Wikibase\Store\ChangeDispatchCoordinator;
use Wikibase\Store\SubscriptionLookup;

/**
 * @covers Wikibase\Repo\ChangeDispatcher
 *
 * @group Wikibase
 * @group WikibaseChange
 * @group WikibaseChangeDispatcher
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeDispatcherTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var array[]
	 */
	private $subscriptions;

	/**
	 * @var Change[]
	 */
	private $changes;

	/**
	 * @var string
	 */
	private $now = '20140303021010';

	/**
	 * @param ChangeDispatchCoordinator $coordinator
	 * @param array[] &$notifications
	 *
	 * @return ChangeDispatcher
	 */
	private function getChangeDispatcher( ChangeDispatchCoordinator $coordinator, &$notifications = array() ) {
		$dispatcher = new ChangeDispatcher(
			$coordinator,
			$this->getNotificationSender( $notifications ),
			$this->getChunkedChangesAccess(),
			$this->getSubscriptionLookup()
		);

		return $dispatcher;
	}

	/**
	 * @param array[] &$notifications An array to receive any notifications,
	 *                each having the form array( $siteID, $changes ).
	 *
	 * @return ChangeNotificationSender
	 */
	private function getNotificationSender( &$notifications = array() ) {
		$sender = $this->getMock( 'Wikibase\Repo\Notifications\ChangeNotificationSender' );

		$sender->expects( $this->any() )
			->method( 'sendNotification' )
			->will(  $this->returnCallback( function ( $siteID, array $changes ) use ( &$notifications ) {
				$notifications[] = array( $siteID, $changes );
			} ) );

		return $sender;
	}

	/**
	 * @return ChunkAccess<Change>
	 */
	private function getChunkedChangesAccess() {
		$chunkedAccess = $this->getMock( 'Wikibase\ChunkAccess' );

		$chunkedAccess->expects( $this->any() )
			->method( 'loadChunk' )
			->will( $this->returnCallback( array( $this, 'getChanges' ) ) );

		$chunkedAccess->expects( $this->any() )
			->method( 'getRecordId' )
			->will( $this->returnCallback( function ( Change $change ) {
				return $change->getId();
			} ) );

		return $chunkedAccess;
	}

	/**
	 * @return SubscriptionLookup
	 */
	private function getSubscriptionLookup() {
		$lookup = $this->getMock( 'Wikibase\Store\SubscriptionLookup' );

		$lookup->expects( $this->any() )
			->method( 'getSubscriptions' )
			->will( $this->returnCallback( array( $this, 'getSubscriptions' ) ) );

		return $lookup;
	}

	public function getChanges( $fromId, $limit ) {
		return array_slice( $this->changes, max( $fromId, 1 ), $limit );
	}

	public function getSubscriptions( $siteId, array $entityIds ) {
		if ( !isset( $this->subscriptions[$siteId] ) ) {
			return array();
		}

		return array_intersect( $this->subscriptions[$siteId], $entityIds );
	}

	/**
	 * @return Change[]
	 */
	private function getAllChanges() {
		$changeId = 0;
		return array(
			// index 0 is ignored, or used as the base change.
			$this->newChange( $changeId++, new ItemId( 'Q11' ), sprintf( '2014030301%2d', $changeId ) ),
			$this->newChange( $changeId++, new ItemId( 'Q11' ), sprintf( '2014030301%2d', $changeId ) ),
			$this->newChange( $changeId++, new ItemId( 'Q11' ), sprintf( '2014030301%2d', $changeId ) ),
			$this->newChange( $changeId++, new ItemId( 'Q22' ), sprintf( '2014030301%2d', $changeId ) ),
			$this->newChange( $changeId++, new ItemId( 'Q22' ), sprintf( '2014030301%2d', $changeId ) ),
			$this->newChange( $changeId++, new ItemId( 'Q33' ), sprintf( '2014030301%2d', $changeId ) ),
			$this->newChange( $changeId++, new ItemId( 'Q33' ), sprintf( '2014030301%2d', $changeId ) ),
			$this->newChange( $changeId++, new ItemId( 'Q44' ), sprintf( '2014030301%2d', $changeId ) ),
			$this->newChange( $changeId++, new ItemId( 'Q44' ), sprintf( '2014030301%2d', $changeId ) ),
		);
	}

	public function setUp() {
		$this->subscriptions['enwiki'] = array(
			new ItemId( 'Q11' ),
			new ItemId( 'Q22' ),
			new ItemId( 'Q33' ),
		);

		$this->subscriptions['dewiki'] = array(
			new ItemId( 'Q22' ),
			new ItemId( 'Q44' ),
		);

		$this->changes = $this->getAllChanges();
	}

	/**
	 * @param int $changeId
	 * @param EntityId $entityId
	 * @param string $time
	 *
	 * @return Change
	 */
	private function newChange( $changeId, EntityId $entityId, $time ) {
		//FIXME: test non-items too!
		$change = $this->getMockBuilder( 'Wikibase\ItemChange' )
			->disableOriginalConstructor()
			->getMock();

		$change->expects( $this->never() )
			->method( 'getType' );

		$change->expects( $this->never() )
			->method( 'getUser' );

		$change->expects( $this->any() )
			->method( 'isEmpty' )
			->will(  $this->returnValue( false ) );

		$change->expects( $this->any() )
			->method( 'getTime' )
			->will(  $this->returnValue( $time ) );

		$change->expects( $this->any() )
			->method( 'getAge' )
			->will(  $this->returnValue( (int)wfTimestamp( TS_UNIX, $time ) - (int)wfTimestamp( TS_UNIX, $this->now ) ) );

		$change->expects( $this->any() )
			->method( 'getId' )
			->will(  $this->returnValue( $changeId ) );

		$change->expects( $this->any() )
			->method( 'getObjectId' )
			->will(  $this->returnValue( $entityId->getNumericId() ) );

		$change->expects( $this->any() )
			->method( 'getEntityId' )
			->will(  $this->returnValue( $entityId ) );

		return $change;
	}

	public function testSelectClient() {
		$siteId = 'testwiki';

		$expectedClientState = array(
			'chd_site' =>   $siteId,
			'chd_db' =>     $siteId,
			'chd_seen' =>   0,
			'chd_touched' => '20140303000000',
			'chd_lock' =>   null
		);

		$coordinator = $this->getMock( 'Wikibase\Store\ChangeDispatchCoordinator' );

		$coordinator->expects( $this->once() )
			->method( 'selectClient' )
			->will(  $this->returnValue( $expectedClientState ) );

		$coordinator->expects( $this->never() )
			->method( 'initState' );

		$dispatcher = $this->getChangeDispatcher( $coordinator );

		// This does nothing but call $coordinator->selectClient()
		$actualClientState = $dispatcher->selectClient();
		$this->assertEquals( $expectedClientState, $actualClientState );
	}

	public function provideGetPendingChanges() {
		$changes = $this->getAllChanges();

		return array(
			array( 'enwiki', 0, 3, 1, array( $changes[1], $changes[2], $changes[3] ), 3 ),
			//FIXME: test more!
		);
	}

	/**
	 * @dataProvider provideGetPendingChanges
	 */
	public function testGetPendingChanges( $siteId, $afterId, $batchSize, $batchChunkFactor, $expectedChanges, $expectedSeen ) {
		$coordinator = $this->getMock( 'Wikibase\Store\ChangeDispatchCoordinator' );

		$dispatcher = $this->getChangeDispatcher( $coordinator );

		$dispatcher->setBatchSize( $batchSize );
		$dispatcher->setBatchChunkFactor( $batchChunkFactor );

		$pending = $dispatcher->getPendingChanges(
			$siteId,
			$afterId
		);

		$this->assertChanges( $expectedChanges, $pending[0] );
		$this->assertEquals( $expectedSeen, $pending[1] );
	}

	public function provideDispatchTo() {
		$changes = $this->getAllChanges();

		return array(
			array(
				3,
				array(
					'chd_site' =>   'enwiki',
					'chd_db' =>     'enwikidb',
					'chd_seen' =>   0,
					'chd_touched' => '00000000000000',
					'chd_lock' =>   null
				),
				$changes[3]->getId(),
				array(
					array( 'enwiki', array( 1, 2, 3 ) )
				)
			),
			//FIXME: test more!
		);
	}

	/**
	 * @dataProvider provideDispatchTo
	 */
	public function testDispatchTo( $batchSize, $wikiState, $expectedFinalSeen, $expectedNotifications ) {
		$expectedFinalState = array_merge( $wikiState, array( 'chd_seen' => $expectedFinalSeen ) );

		$coordinator = $this->getMock( 'Wikibase\Store\ChangeDispatchCoordinator' );

		$coordinator->expects( $this->never() )
			->method( 'lockClient' );

		$coordinator->expects( $this->once() )
			->method( 'releaseClient' )
			->with( $expectedFinalState );

		$dispatcher = $this->getChangeDispatcher(
			$coordinator,
			$notifications
		);

		$dispatcher->setBatchSize( $batchSize );

		$dispatcher->dispatchTo(
			$wikiState
		);

		$this->assertNotifications( $expectedNotifications, $notifications );
	}

	private function getChangeIds( array $changes ) {
		return array_map( function( Change $change ) {
			return $change->getId();
		}, $changes );
	}

	private function assertChanges( $expected, $actual ) {
		$expected = $this->getChangeIds( $expected );
		$actual = $this->getChangeIds( $actual );

		$this->assertEquals( $expected, $actual );
	}

	private function assertNotifications( $expected, $notifications ) {
		foreach ( $notifications as &$n ) {
			$n[1] = $this->getChangeIds( $n[1] );
		}

		$this->assertEquals( $expected, $notifications );
	}

}
