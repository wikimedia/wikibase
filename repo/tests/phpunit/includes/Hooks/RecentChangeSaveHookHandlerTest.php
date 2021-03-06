<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Hooks;

use CentralIdLookup;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RecentChange;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Repo\Hooks\RecentChangeSaveHookHandler;

/**
 * @covers \Wikibase\Repo\Hooks\RecentChangeSaveHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RecentChangeSaveHookHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var EntityChangeLookup|MockObject
	 */
	private $changeLookup;

	/**
	 * @var ChangeStore|MockObject
	 */
	private $changeStore;

	/**
	 * @var null|CentralIdLookup|MockObject
	 */
	private $centralIdLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->changeLookup = $this->createStub( EntityChangeLookup::class );
		$this->changeStore = $this->createStub( ChangeStore::class );
		$this->centralIdLookup = null; // CentralIdLookupFactory::getNonLocalLookup() may return null in the hook's factory function
	}

	public function testGivenRecentChangeForEntityChange_addsMetaDataToEntityChange() {
		$recentChangeAttrs = [
			'rc_timestamp' => 1234567890,
			'rc_bot' => 1,
			'rc_cur_id' => 42,
			'rc_last_oldid' => 776,
			'rc_this_oldid' => 777,
			'rc_comment' => 'edit summary',
			'rc_user' => 321,
			'rc_user_text' => 'some_user',
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$entityChange = $this->newEntityChange();

		$this->changeLookup = $this->createMock( EntityChangeLookup::class );
		$this->changeLookup->expects( $this->once() )
			->method( 'loadByRevisionId' )
			->with( $recentChangeAttrs['rc_this_oldid'] )
			->willReturn( $entityChange );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$changeMetaData = $entityChange->getMetadata();
		$this->assertSame( $recentChangeAttrs['rc_bot'], $changeMetaData['bot'] );
		$this->assertSame( $recentChangeAttrs['rc_cur_id'], $changeMetaData['page_id'] );
		$this->assertSame( $recentChangeAttrs['rc_this_oldid'], $changeMetaData['rev_id'] );
		$this->assertSame( $recentChangeAttrs['rc_last_oldid'], $changeMetaData['parent_id'] );
		$this->assertSame( $recentChangeAttrs['rc_comment'], $changeMetaData['comment'] );
	}

	public function testGivenCentralIdLookupAndRecentChangeWithUser_addsUserIdToEntityChange() {
		$expectedUserId = 123;
		$testUser = $this->getTestUser()->getUser();
		$recentChangeAttrs = [
			'rc_this_oldid' => 777,
			'rc_user' => $testUser->getId(),
			'rc_user_text' => $testUser->getName(),
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$recentChange->method( 'getPerformerIdentity' )
			->willReturn( $testUser );
		$entityChange = $this->newEntityChange();

		$this->changeLookup = $this->createMock( EntityChangeLookup::class );
		$this->changeLookup->expects( $this->once() )
			->method( 'loadByRevisionId' )
			->with( $recentChangeAttrs['rc_this_oldid'] )
			->willReturn( $entityChange );

		$this->centralIdLookup = $this->createMock( CentralIdLookup::class );
		$this->centralIdLookup->expects( $this->once() )
			->method( 'centralIdFromLocalUser' )
			->willReturn( $expectedUserId );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$this->assertSame( $testUser->getId(), $entityChange->getField( 'user_id' ) );

		$changeMetaData = $entityChange->getMetadata();
		$this->assertSame( $testUser->getName(), $changeMetaData['user_text'] );
		$this->assertSame( $expectedUserId, $changeMetaData['central_user_id'] );
	}

	private function newHookHandler(): RecentChangeSaveHookHandler {
		return new RecentChangeSaveHookHandler(
			$this->changeLookup,
			$this->changeStore,
			$this->centralIdLookup
		);
	}

	private function newStubRecentChangeWithAttributes( array $attributes ): RecentChange {
		$rc = $this->createStub( RecentChange::class );
		$rc->method( 'getAttribute' )
			->willReturnCallback( function ( $key ) use ( $attributes ) {
				return $attributes[$key] ?? null;
			} );

		return $rc;
	}

	private function newEntityChange(): EntityChange {
		return new EntityChange( [ 'type' => 'wikibase-someEntity~update' ] );
	}

}
