<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use OutOfBoundsException;
use Revision;
use TestSites;
use TestUser;
use Title;
use ApiUsageException;
use User;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
abstract class WikibaseApiTestCase extends ApiTestCase {

	protected function setUp() {
		parent::setUp();

		static $isSetup = false;

		$this->setupUser();

		$siteStore = new \HashSiteStore( TestSites::getSites() );
		$this->setService( 'SiteStore', $siteStore );
		$this->setService( 'SiteLookup', $siteStore );

		if ( !$isSetup ) {
			$this->doLogin( 'wbeditor' );
			$isSetup = true;
		}
	}

	protected function createTestUser() {
		return new TestUser(
			'Apitesteditor',
			'Api Test Editor',
			'api_test_editor@example.com',
			[ 'wbeditor' ]
		);
	}

	private function setupUser() {
		self::$users['wbeditor'] = $this->createTestUser();

		$this->setMwGlobals( 'wgUser', self::$users['wbeditor']->getUser() );
		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [
			'property-create' => true,
			'createpage' => true,
			'bot' => true,
			'item-term' => true,
			'item-merge' => true,
			'item-redirect' => true,
			'property-term' => true,
			'read' => true,
			'edit' => true,
			'writeapi' => true
		] ] );
	}

	/**
	 * Appends an edit token to a request.
	 *
	 * @param array $params
	 * @param array|null $session
	 * @param User|null $user
	 * @param string $tokenType
	 *
	 * @throws ApiUsageException
	 * @return array( array|null $resultData, WebRequest $request, array $sessionArray )
	 */
	protected function doApiRequestWithToken(
		array $params,
		array $session = null,
		User $user = null,
		$tokenType = 'csrf'
	) {
		if ( !$user ) {
			$user = \RequestContext::getMain()->getUser();
		}

		if ( !array_key_exists( 'token', $params ) ) {
			$params['token'] = $user->getEditToken();
		}

		return $this->doApiRequest( $params, $session, false, $user, $tokenType );
	}

	/**
	 * @param string[] $handles
	 * @param string[] $idMap
	 */
	protected function initTestEntities( array $handles, array $idMap = [] ) {
		$activeHandles = EntityTestHelper::getActiveHandles();

		foreach ( $activeHandles as $handle => $id ) {
			$title = $this->getTestEntityTitle( $handle );

			$page = WikiPage::factory( $title );
			$page->doDeleteArticle( 'Test reset' );
			EntityTestHelper::unRegisterEntity( $handle );
		}

		foreach ( $handles as $handle ) {
			$params = EntityTestHelper::getEntity( $handle );
			$params['action'] = 'wbeditentity';

			EntityTestHelper::injectIds( $params, $idMap );
			EntityTestHelper::injectIds( $params, EntityTestHelper::$defaultPlaceholderValues );

			list( $res, , ) = $this->doApiRequestWithToken( $params );
			EntityTestHelper::registerEntity( $handle, $res['entity']['id'], $res['entity'] );

			$idMap["%$handle%"] = $res['entity']['id'];
		}
	}

	/**
	 * @param string $handle
	 *
	 * @return null|Title
	 */
	protected function getTestEntityTitle( $handle ) {
		try {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$idString = EntityTestHelper::getId( $handle );
			$id = $wikibaseRepo->getEntityIdParser()->parse( $idString );
			$title = $wikibaseRepo->getEntityTitleLookup()->getTitleForId( $id );
		} catch ( OutOfBoundsException $ex ) {
			$title = null;
		}

		return $title;
	}

	/**
	 * Loads an entity from the database (via an API call).
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function loadEntity( $id ) {
		list( $res, , ) = $this->doApiRequest(
			[
				'action' => 'wbgetentities',
				'format' => 'json', // make sure IDs are used as keys.
				'ids' => $id ]
		);

		return $res['entities'][$id];
	}

	/**
	 * Do the test for exceptions from Api queries.
	 *
	 * @param array $params Array of params for the API query.
	 * @param array $exception Details of the exception to expect (type, code, message, message-key).
	 * @param User $user
	 */
	protected function doTestQueryExceptions( array $params, array $exception, User $user = null ) {
		try {
			if ( array_key_exists( 'code', $exception )
				&& preg_match( '/^(no|bad)token$/', $exception['code'] )
			) {
				$this->doApiRequest( $params, null, false, $user );
			} else {
				$this->doApiRequestWithToken( $params, null, $user );
			}

			$this->fail( 'Failed to throw ApiUsageException' );
		} catch ( ApiUsageException $e ) {
			if ( array_key_exists( 'type', $exception ) ) {
				$this->assertInstanceOf( $exception['type'], $e );
			}

			if ( array_key_exists( 'code', $exception ) ) {
				$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
				$this->assertEquals( $exception['code'], $msg->getApiCode() );
			}

			if ( array_key_exists( 'message', $exception ) ) {
				$this->assertContains( $exception['message'], $e->getMessage() );
			}

			if ( array_key_exists( 'message-key', $exception ) ) {
				$status = $e->getStatusValue();
				$this->assertTrue(
					$status->hasMessage( $exception['message-key'] ),
					'Status message key'
				);
			}
		}
	}

	/**
	 * Utility function for converting an array from "deep" (indexed) to "flat" (keyed) structure.
	 * Arrays that already use a flat structure are left unchanged.
	 *
	 * Arrays with a deep structure are expected to be list of entries that are associative arrays,
	 * where which entry has at least the fields given by $keyField and $valueField.
	 *
	 * Arrays with a flat structure are associative and assign values to meaningful keys.
	 *
	 * @param array $data the input array.
	 * @param string $keyField The name of the field in each entry that shall be used as the key in
	 *  the flat structure.
	 * @param string $valueField The name of the field in each entry that shall be used as the value
	 *  in the flat structure.
	 * @param bool $multiValue Whether the value in the flat structure shall be an indexed array of
	 *  values instead of a single value.
	 * @param array &$into optional aggregator.
	 *
	 * @return array The flat version of $data.
	 */
	protected function flattenArray( array $data, $keyField, $valueField, $multiValue = false, array &$into = [] ) {
		foreach ( $data as $index => $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value[$keyField] ) && isset( $value[$valueField] ) ) {
					// found "deep" entry in the array
					$k = $value[ $keyField ];
					$v = $value[ $valueField ];
				} elseif ( isset( $value[0] ) && !is_array( $value[0] ) && $multiValue ) {
					// found "flat" multi-value entry in the array
					$k = $index;
					$v = $value;
				} else {
					// found list, recurse
					$this->flattenArray( $value, $keyField, $valueField, $multiValue, $into );
					continue;
				}
			} else {
				// found "flat" entry in the array
				$k = $index;
				$v = $value;
			}

			if ( $multiValue ) {
				if ( is_array( $v ) ) {
					$into[$k] = empty( $into[$k] ) ? $v : array_merge( $into[$k], $v );
				} else {
					$into[$k][] = $v;
				}
			} else {
				$into[$k] = $v;
			}
		}

		return $into;
	}

	/**
	 * Compares two entity structures and asserts that they are equal. Only fields present in $expected are considered.
	 * $expected and $actual can both be either in "flat" or in "deep" form, they are converted as needed before comparison.
	 *
	 * @param array $expected
	 * @param array $actual
	 * @param bool $expectEmptyArrays Should we expect empty arrays or just ignore them?
	 */
	protected function assertEntityEquals( array $expected, array $actual, $expectEmptyArrays = true ) {
		if ( isset( $expected['id'] ) && !empty( $expected['id'] ) ) {
			$this->assertEquals( $expected['id'], $actual['id'], 'id' );
		}
		if ( isset( $expected['lastrevid'] ) ) {
			$this->assertEquals( $expected['lastrevid'], $actual['lastrevid'], 'lastrevid' );
		}
		if ( isset( $expected['type'] ) ) {
			$this->assertEquals( $expected['type'], $actual['type'], 'type' );
		}

		if ( isset( $expected['labels'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['labels'] === [] ) ) {
				$data = $this->flattenArray( $actual['labels'], 'language', 'value' );
				$exp = $this->flattenArray( $expected['labels'], 'language', 'value' );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['descriptions'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['descriptions'] === [] ) ) {
				$data = $this->flattenArray( $actual['descriptions'], 'language', 'value' );
				$exp = $this->flattenArray( $expected['descriptions'], 'language', 'value' );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['sitelinks'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['sitelinks'] === [] ) ) {
				$data = $this->flattenArray( isset( $actual['sitelinks'] ) ? $actual['sitelinks'] : [], 'site', 'title' );
				$exp = $this->flattenArray( $expected['sitelinks'], 'site', 'title' );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['aliases'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['aliases'] === [] ) ) {
				$data = $this->flattenArray( $actual['aliases'], 'language', 'value', true );
				$exp = $this->flattenArray( $expected['aliases'], 'language', 'value', true );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['claims'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['claims'] === [] ) ) {
				$data = $this->flattenArray( $actual['claims'], 'mainsnak', 'value', true );
				$exp = $this->flattenArray( $expected['claims'], 'language', 'value', true );
				$count = count( $expected['claims'] );

				for ( $i = 0; $i < $count; $i++ ) {
					$this->assertArrayHasKey( $i, $data['id'] );
					$this->assertGreaterThanOrEqual( 39, strlen( $data['id'][$i] ) );
				}
				//unset stuff we dont actually want to compare
				if ( isset( $exp['id'] ) ) {
					$this->assertArrayHasKey( 'id', $data );
				}
				unset( $exp['id'] );
				unset( $exp['datatype'] );
				unset( $exp['hash'] );
				unset( $exp['qualifiers-order'] );
				unset( $data['datatype'] );
				unset( $data['id'] );
				unset( $data['hash'] );
				unset( $data['qualifiers-order'] );
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}
	}

	/**
	 * Asserts that the given API response represents a successful call.
	 *
	 * @param array $response
	 */
	protected function assertResultSuccess( array $response ) {
		$this->assertArrayHasKey( 'success', $response, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $response );
	}

	/**
	 * Asserts that the given API response has a valid entity type if the result contains an entity
	 *
	 * @param array $response
	 */
	protected function assertResultHasEntityType( array $response ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		if ( isset( $response['entity'] ) ) {
			if ( isset( $response['entity']['type'] ) ) {
				$this->assertContains(
					$response['entity']['type'],
					$wikibaseRepo->getEnabledEntityTypes(),
					"Missing valid 'type' in response."
				);
			}
		} elseif ( isset( $response['entities'] ) ) {
			foreach ( $response['entities'] as $entity ) {
				if ( isset( $entity['type'] ) ) {
					$this->assertContains(
						$entity['type'],
						$wikibaseRepo->getEnabledEntityTypes(),
						"Missing valid 'type' in response."
					);
				}
			}
		}
	}

	/**
	 * Asserts that the revision with the given ID has a summary matching $regex
	 *
	 * @param string|string[] $regex The regex to match, or an array to build a regex from.
	 * @param int $revid
	 */
	protected function assertRevisionSummary( $regex, $revid ) {
		if ( is_array( $regex ) ) {
			$r = '';

			foreach ( $regex as $s ) {
				if ( $r !== '' ) {
					$r .= '.*';
				}

				$r .= preg_quote( $s, '!' );
			}

			$regex = "!$r!";
		}

		$rev = Revision::newFromId( $revid );
		$this->assertNotNull( $rev, "revision not found: $revid" );

		$comment = $rev->getComment();
		$this->assertRegExp( $regex, $comment );
	}

}
