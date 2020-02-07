<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use ContentHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \Wikibase\Repo\Api\EditEntity
 * @covers \Wikibase\Repo\Api\ModifyEntity
 *
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 */
class EditEntityFingerprintUniquenessIntegrationTest extends WikibaseApiTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'wb_terms';
		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_property_terms';
		$this->tablesUsed[] = 'wbt_item_terms';
	}

	public function tearDown() : void {
		parent::tearDown();
		// Cleaning ContentHandler cache because RepoHooks instantiate
		// and cache those prior to changing the migration setting
		// as part of each test-case
		ContentHandler::cleanupHandlersCache();
	}

	protected function getSchemaOverrides( IMaintainableDatabase $db ) {
		return [
			'scripts' => [ $this->getSqlFileAbsolutePath() ],
			'create' => [
				'wbt_item_terms',
				'wbt_property_terms',
				'wbt_term_in_lang',
				'wbt_text_in_lang',
				'wbt_text',
				'wbt_type',
			],
		];
	}

	private function getSqlFileAbsolutePath() {
		return __DIR__ . '/../../../../sql/AddNormalizedTermsTablesDDL.sql';
	}

	public function propertyLabelConflictTestProvider() {
		return [
			[
				'migrationStage' => MIGRATION_OLD,
				'expectedFailureCode' => 'failed-save'
			],

			[
				'migrationStage' => MIGRATION_WRITE_BOTH,
				'expectedFailureCode' => 'failed-save'
			],

			[
				'migrationStage' => MIGRATION_WRITE_NEW,
				'expectedFailureCode' => 'modification-failed'
			],

			[
				'migrationStage' => MIGRATION_NEW,
				'expectedFailureCode' => 'modification-failed'
			]
		];
	}

	/**
	 * @dataProvider propertyLabelConflictTestProvider
	 */
	public function testNewPropertyLabelConflict( $migrationStage, $expectedFailureCode ) {
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting(
			'tmpPropertyTermsMigrationStage',
			$migrationStage
		);

		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'datatype' => 'string',
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ]
			] ),
			'new' => 'property',
		];
		$this->doApiRequestWithToken( $params );

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => $expectedFailureCode,
		];
		// Repeating the same request with the same label should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	/**
	 * @dataProvider propertyLabelConflictTestProvider
	 */
	public function testExistingPropertyLabelConflict( $migrationStage, $expectedFailureCode ) {
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting(
			'tmpPropertyTermsMigrationStage',
			$migrationStage
		);

		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'datatype' => 'string',
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ]
			] ),
			'new' => 'property',
		];
		$this->doApiRequestWithToken( $params );

		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'datatype' => 'string',
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'no conflict label' ] ]
			] ),
			'new' => 'property',
		];
		$existingPropertyId = $this->doApiRequestWithToken( $params )[0]['entity']['id'];

		$params = [
			'action' => 'wbeditentity',
			'id' => $existingPropertyId,
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
			] ),
		];

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => $expectedFailureCode,
		];
		// Repeating the same request with the same label should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	public function itemLabelWithoutDescriptionNotConflictingTestProvider() {
		return [
			[
				'migrationStage' => [ 'max' => MIGRATION_OLD ],
			],

			[
				'migrationStage' => [ 'max' => MIGRATION_WRITE_BOTH ],
			],

			[
				'migrationStage' => [ 'max' => MIGRATION_WRITE_NEW ],
			],

			[
				'migrationStage' => [ 'max' => MIGRATION_NEW ],
			]
		];
	}

	/**
	 * @dataProvider itemLabelWithoutDescriptionNotConflictingTestProvider
	 */
	public function testItemLabelWithoutDescriptionNotConflicting( $migrationStage ) {
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting(
			'tmpItemTermsMigrationStages',
			$migrationStage
		);

		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'no conflict label' ] ]
			] ),
			'new' => 'item',
		];
		$this->doApiRequestWithToken( $params );

		// Repeating the same request with the same label should not fail.
		list( $result, , ) = $this->doApiRequestWithToken( $params );
		$this->assertArrayHasKey( 'success', $result );
	}

	public function itemLabelDescriptionConflictTestProvider() {
		return [
			[
				'migrationStage' => [ 'max' => MIGRATION_OLD ],
				'expectedFailureCode' => 'modification-failed'
			],

			[
				'migrationStage' => [ 'max' => MIGRATION_WRITE_BOTH ],
				'expectedFailureCode' => 'modification-failed'
			],

			[
				'migrationStage' => [ 'max' => MIGRATION_WRITE_NEW ],
				'expectedFailureCode' => 'modification-failed'
			],

			[
				'migrationStage' => [ 'max' => MIGRATION_NEW ],
				'expectedFailureCode' => 'modification-failed'
			]
		];
	}

	/**
	 * @dataProvider itemLabelDescriptionConflictTestProvider
	 */
	public function testNewItemLabelDescriptionConflict( $migrationStage, $expectedFailureCode ) {
		// When $migrationStage < MIGRATION_WRITE_NEW old store uniqueness validation
		// will be executed, it relies on self-joining wb_terms which is not supported
		// by mysql on temporary tables
		if ( $migrationStage['max'] < MIGRATION_WRITE_NEW
			&& $this->usesTemporaryTables()
			&& $this->db->getType() === 'mysql'
		) {
			$this->markTestSkipped( 'MySQL doesn\'t support self-joins on temporary tables' );
		}

		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting(
			'tmpItemTermsMigrationStages',
			$migrationStage
		);

		$params = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
				'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict description' ] ],
			] ),
		];
		$this->doApiRequestWithToken( $params );

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => $expectedFailureCode,
		];
		// Repeating the same request with the same label and description should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	/**
	 * @dataProvider itemLabelDescriptionConflictTestProvider
	 */
	public function testExistingItemLabelDescriptionConflict( $migrationStage, $expectedFailureCode ) {
		// When $migrationStage < MIGRATION_WRITE_NEW old store uniqueness validation
		// will be executed, it relies  on self-jopining wb_terms which is not supported
		// by mysql on temporary tables
		if ( $migrationStage['max'] < MIGRATION_WRITE_NEW
			&& $this->usesTemporaryTables()
			&& $this->db->getType() === 'mysql'
		) {
			$this->markTestSkipped( 'MySQL doesn\'t support self-joins on temporary tables' );
		}

		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting(
			'tmpItemTermsMigrationStages',
			$migrationStage
		);

		$params = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
				'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict description' ] ],
			] ),
		];
		$this->doApiRequestWithToken( $params );

		$params = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
				'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'no conflict description' ] ],
			] ),
		];
		$existingItemId = $this->doApiRequestWithToken( $params )[0]['entity']['id'];

		$params = [
			'action' => 'wbeditentity',
			'id' => $existingItemId,
			'data' => json_encode( [
				'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict description' ] ],
			] ),
		];

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => $expectedFailureCode,
		];
		// Repeating the same request with the same label and description should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

}
