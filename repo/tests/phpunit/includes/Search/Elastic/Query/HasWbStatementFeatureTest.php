<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Query;

use CirrusSearch\CrossSearchStrategy;
use CirrusSearch\Query\BaseSimpleKeywordFeatureTest;
use Wikibase\Repo\Search\Elastic\Query\HasWbStatementFeature;

/**
 * @covers \Wikibase\Repo\Search\Elastic\Query\HasWbStatementFeature
 *
 * @group WikibaseElastic
 * @group Wikibase
 */
class HasWbStatementFeatureTest extends BaseSimpleKeywordFeatureTest {

	public function applyProvider() {
		return [
			'single statement entity' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=Q888',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:P999=Q888',
				'foreignRepoNames' => [],
			],
			'single statement string' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=12345',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:P999=12345',
				'foreignRepoNames' => [],
			],
			'single statement federated' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'Federated:P999=Federated:Q888',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:Federated:P999=Federated:Q888',
				'foreignRepoNames' => [ 'Federated' ],
			],
			'multiple statements' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'Federated:P999=Q888',
							],
						] ],
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P777=someString',
							],
						] ]
					]
				] ],
				'search string' => 'haswbstatement:Federated:P999=Q888|P777=someString',
				'foreignRepoNames' => [ 'Federated' ],
			],
			'some data invalid' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'P999=Q888',
							],
						] ],
					]
				] ],
				'search string' => 'haswbstatement:INVALID|P999=Q888',
				'foreignRepoNames' => [],
			],
			'invalid foreign repo name rejected' => [
				'expected' => [ 'bool' => [
					'should' => [
						[ 'match' => [
							'statement_keywords' => [
								'query' => 'Federated:P999=Q888',
							],
						] ],
					]
				] ],
				'search string' => 'haswbstatement:INVALID_FOREIGN_REPO:P999=P777|' .
					'Federated:P999=Q888',
				'foreignRepoNames' => [ 'Federated' ],
			],
			'all data invalid' => [
				'expected' => null,
				'search string' => 'haswbstatement:INVALID',
				'foreignRepoNames' => [],
			],
		];
	}

	/**
	 * @dataProvider applyProvider
	 */
	public function testApply( array $expected = null, $term, $foreignRepoNames ) {
		$feature = new HasWbStatementFeature( $foreignRepoNames );
		$expectedWarnings = $expected !== null ? [ [ 'cirrussearch-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ] : [];
		$this->assertFilter( $feature, $term, $expected, $expectedWarnings );
		$this->assertCrossSearchStrategy( $feature, $term, CrossSearchStrategy::hostWikiOnlyStrategy() );
		if ( $expected === null ) {
			$this->assertNoResultsPossible( $feature, $term );
		}
	}

	public function applyNoDataProvider() {
		return [
			'empty data' => [
				'haswbstatement:',
			],
			'no data' => [
				'',
			],
		];
	}

	/**
	 * @dataProvider applyNoDataProvider
	 */
	public function testNotConsumed( $term ) {
		$feature = new HasWbStatementFeature( [ 'P999' ] );
		$this->assertNotConsumed( $feature, $term );
	}

	public function testInvalidStatementWarning() {
		$feature = new HasWbStatementFeature( [ 'P999' ] );
		$expectedWarnings = [ [ 'cirrussearch-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ];
		$this->assertParsedValue( $feature, 'haswbstatement:INVALID', [ 'statements' => [] ], $expectedWarnings );
		$this->assertExpandedData( $feature, 'haswbstatement:INVALID', [], [] );
		$this->assertFilter( $feature, 'haswbstatement:INVALID', null, $expectedWarnings );
		$this->assertNoResultsPossible( $feature, 'haswbstatement:INVALID' );
	}

	/**
	 * @dataProvider parseProvider
	 */
	public function testParseValue( $foreignRepoNames, $value, $expected, $warningExpected ) {
		$feature = new HasWbStatementFeature( $foreignRepoNames );
		$expectedWarnings = $warningExpected ? [ [ 'cirrussearch-haswbstatement-feature-no-valid-statements', 'haswbstatement' ] ] : [];
		$this->assertParsedValue( $feature, "haswbstatement:\"$value\"", [ 'statements' => $expected ], $expectedWarnings );
	}

	public function parseProvider() {
		return [
			'empty value' => [
				'foreignRepoNames' => [],
				'value' => '',
				'expected' => [],
				'warningExpected' => true,
			],
			'invalid value' => [
				'foreignRepoNames' => [],
				'value' => 'xyz=12345',
				'expected' => [],
				'warningExpected' => true,
			],
			'invalid federated value' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikisource:P123=12345',
				'expected' => [],
				'warningExpected' => true,
			],
			'single value Q-id' => [
				'foreignRepoNames' => [],
				'value' => 'P999=Q888',
				'expected' => [ 'P999=Q888' ],
				'warningExpected' => false,
			],
			'single value other id' => [
				'foreignRepoNames' => [],
				'value' => 'P999=AB123',
				'expected' => [ 'P999=AB123' ],
				'warningExpected' => false,
			],
			'single value federated' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999=Wikidata:Q888',
				'expected' => [ 'Wikidata:P999=Wikidata:Q888' ],
				'warningExpected' => false,
			],
			'multiple values' => [
				'foreignRepoNames' => [ 'Wikidata', 'Wikisource' ],
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikisource:P777=12345',
				'expected' => [
					'Wikidata:P999=Wikidata:Q888',
					'Wikisource:P777=12345',
				],
				'warningExpected' => false,
			],
			'multiple values, not all valid' => [
				'foreignRepoNames' => [ 'Wikidata' ],
				'value' => 'Wikidata:P999=Wikidata:Q888|Wikisource:P777=12345',
				'expected' => [
					'Wikidata:P999=Wikidata:Q888',
				],
				'warningExpected' => false,
			],
		];
	}

}
