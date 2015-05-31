<?php

namespace Wikibase\Test\Api;

use Language;
use Status;
use User;
use Wikibase\Api\ApiErrorReporter;
use Wikibase\Api\MergeItems;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\EntityModificationTestHelper;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Api\MergeItems
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group MergeItemsTest
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Lucie-Aimée Kaffee
 */
class MergeItemsTest extends \MediaWikiTestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepository = null;

	/**
	 * @var EntityModificationTestHelper|null
	 */
	private $entityModificationTestHelper = null;

	/**
	 * @var ApiModuleTestHelper|null
	 */
	private $apiModuleTestHelper = null;

	protected function setUp() {
		parent::setUp();

		$this->entityModificationTestHelper = new EntityModificationTestHelper();
		$this->apiModuleTestHelper = new ApiModuleTestHelper();

		$this->mockRepository = $this->entityModificationTestHelper->getMockRepository();

		$this->entityModificationTestHelper->putEntities( array(
			'Q1' => array(),
			'Q2' => array(),
			'P1' => array( 'datatype' => 'string' ),
			'P2' => array( 'datatype' => 'string' ),
		) );

		$this->entityModificationTestHelper->putRedirects( array(
			'Q11' => 'Q1',
			'Q12' => 'Q2',
		) );
	}

	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user, $permission ) {
				if ( $user->getName() === 'UserWithoutPermission' && $permission === 'edit' ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}
        
        /**
	 * @return EditFilterHookRunner
	 */
	public function getMockEditFilterHookRunner() {
                $mock = $this->getMockBuilder( 'Wikibase\Repo\Hooks\EditFilterHookRunner' )
                        ->disableOriginalConstructor()
                        ->getMock();
                
                $mock->expects( $this->any() )
                       ->method( 'run' )
                        ->will( $this->returnValue( Status::newGood() ) );
                
                return $mock;
	}

	/**
	 * @param MergeItems $module
	 */
	private function overrideServices( MergeItems $module ) {
		$idParser = new BasicEntityIdParser();

		$errorReporter = new ApiErrorReporter(
			$module,
			WikibaseRepo::getDefaultInstance()->getExceptionLocalizer(),
			Language::factory( 'en' )
		);

		$resultBuilder = WikibaseRepo::getDefaultInstance()->getApiHelperFactory()->getResultBuilder( $module );
		$summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$changeOpsFactory = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()->getMergeChangeOpFactory();
                $wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$module->setServices(
			$idParser,
			$errorReporter,
			$resultBuilder,
			new ItemMergeInteractor(
				$changeOpsFactory,
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				$wikibaseRepo->getSummaryFormatter(),
				$module->getUser(),
                                new RedirectCreationInteractor(
                                        $this->mockRepository,
                                        $this->mockRepository,
                                        $this->getPermissionCheckers(),
                                        $wikibaseRepo->getSummaryFormatter(),
                                        $module->getUser(),
                                        $this->getMockEditFilterHookRunner(),
                                        $this->mockRepository
                                )
			)
		);
	}

	private function callApiModule( $params, User $user = null ) {
		$module = $this->apiModuleTestHelper->newApiModule( 'Wikibase\Api\MergeItems', 'wbmergeitems', $params, $user );
		$this->overrideServices( $module );

		$module->execute();

		$data = $module->getResult()->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
		return $data;
	}

	public function provideData() {
		$testCases = array();
		$testCases['labelMerge'] = array(
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			array(),
			array(),
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
		);
		$testCases['IgnoreConflictSitelinksMerge'] = array(
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainFrom' ),
				'enwiki' => array( 'site' => 'enwiki', 'title' => 'PlFrom' ),
			) ),
			array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainTo' ) ) ),
			array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainFrom' ) ) ),
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainTo' ),
				'enwiki' => array( 'site' => 'enwiki', 'title' => 'PlFrom' ),
			) ),
			'sitelink'
		);
		$testCases['claimMerge'] = array(
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal', 'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ) ) ) ),
			array(),
			array(),
			array( 'claims' => array( 'P1' => array ( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal' ) ) ) ),
		);

		return $testCases;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testMergeRequest( $pre1, $pre2, $expectedFrom, $expectedTo, $ignoreConflicts = null ){
		// -- set up params ---------------------------------
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
			'summary' => 'CustomSummary!',
		);
		if( $ignoreConflicts !== null ){
			$params['ignoreconflicts'] = $ignoreConflicts;
		}

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		// -- do the request --------------------------------------------
		$result = $this->callApiModule( $params );

		// -- check the result --------------------------------------------
                $this->assertResultCorrect( $result );

		// -- check the items --------------------------------------------
		$this->assertItemsCorrect( $result, $expectedFrom, $expectedTo );

		// -- check the edit summaries --------------------------------------------
                $this->assertEditSummariesCorrect( $result );
	}

        private function assertResultCorrect( $result ) {
                $this->apiModuleTestHelper->assertResultSuccess( $result );

		$this->apiModuleTestHelper->assertResultHasKeyInPath( array( 'from', 'id' ), $result );
		$this->apiModuleTestHelper->assertResultHasKeyInPath( array( 'to', 'id' ), $result );
		$this->assertEquals( 'Q1', $result['from']['id'] );
		$this->assertEquals( 'Q2', $result['to']['id'] );

		$this->apiModuleTestHelper->assertResultHasKeyInPath( array( 'from', 'lastrevid' ), $result );
		$this->apiModuleTestHelper->assertResultHasKeyInPath( array( 'to', 'lastrevid' ), $result );
		$this->assertGreaterThan( 0, $result['from']['lastrevid'] );
		$this->assertGreaterThan( 0, $result['to']['lastrevid'] );
        }

        private function assertItemsCorrect( $result, $expectedFrom, $expectedTo ) {
                $actualFrom = $this->entityModificationTestHelper->getEntity( $result['from']['id'], true ); //resolve redirects
		$this->entityModificationTestHelper->assertEntityEquals( $expectedFrom, $actualFrom );

		$actualTo = $this->entityModificationTestHelper->getEntity( $result['to']['id'], true );
		$this->entityModificationTestHelper->assertEntityEquals( $expectedTo, $actualTo );
        }

        private function assertEditSummariesCorrect( $result ) {
                $this->entityModificationTestHelper->assertRevisionSummary( array( 'wbmergeitems' ), $result['from']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( "/CustomSummary/" , $result['from']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( array( 'wbmergeitems' ), $result['to']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( "/CustomSummary/" , $result['to']['lastrevid'] );
        }

	public function provideExceptionParamsData() {
		return array(
			array( //0 no ids given
				'p' => array( ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //1 only from id
				'p' => array( 'fromid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //2 only to id
				'p' => array( 'toid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //3 toid bad
				'p' => array( 'fromid' => 'Q1', 'toid' => 'ABCDE' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
			array( //4 fromid bad
				'p' => array( 'fromid' => 'ABCDE', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
			array( //5 both same id
				'p' => array( 'fromid' => 'Q1', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id', 'message' => 'You must provide unique ids' ) ) ),
			array( //6 from id is property
				'p' => array( 'fromid' => 'P1', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
			array( //7 to id is property
				'p' => array( 'fromid' => 'Q1', 'toid' => 'P1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
			array( //8 bad ignoreconficts
				'p' => array( 'fromid' => 'Q2', 'toid' => 'Q2', 'ignoreconflicts' => 'foo' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
			array( //9 bad ignoreconficts
				'p' => array( 'fromid' => 'Q2', 'toid' => 'Q2', 'ignoreconflicts' => 'label|foo' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
		);
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbmergeitems';

		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected UsageException!' );
		} catch ( \UsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( $expected, $ex );
		}
	}

	public function provideExceptionConflictsData() {
		return array(
			array(
				array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
				array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo2' ) ) ),
			),
			array(
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo2' ) ) ),
			),
		);
	}

	/**
	 * @dataProvider provideExceptionConflictsData
	 */
	public function testMergeItemsConflictsExceptions( $pre1, $pre2 ){
		$expected = array( 'exception' => array( 'type' => 'UsageException', 'code' => 'failed-save' ) );

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
		);

		// -- do the request --------------------------------------------
		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected UsageException!' );
		} catch ( \UsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( $expected, $ex );
		}
	}

	public function testMergeNonExistingItem() {
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978'
		);

		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected UsageException!' );
		} catch ( \UsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( 'no-such-entity', $ex );
		}
	}

}
