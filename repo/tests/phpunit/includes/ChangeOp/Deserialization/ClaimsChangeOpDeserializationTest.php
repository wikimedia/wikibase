<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Set of test methods that can be reused in ClaimsChangeOpDeserializerTest and tests for
 * ChangeOpDeserializers of entities that have claims
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
trait ClaimsChangeOpDeserializationTest {

	/**
	 * @dataProvider setStatementProvider
	 */
	public function testGivenNewStatementChangeRequest_setsStatement( $changeRequest, EntityDocument $entity, $property ) {
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( $changeRequest );
		$changeOp->apply( $entity, new Summary() );

		$this->assertFalse(
			$entity->getStatements()->getByPropertyId( $property )->isEmpty()
		);
	}

	public function setStatementProvider() {
		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $property ) );
		$statementSerialization = $this->getStatementSerializer()->serialize( $statement );
		$entity = $this->getEntity();

		return [
			'numeric index format' => [ [ 'claims' => [ $statementSerialization ] ], $entity, $property ],
			'associative format' => [ [ 'claims' => [ 'P7' => [ $statementSerialization ] ] ], $entity, $property ],
		];
	}

	/**
	 * @dataProvider deleteStatementProvider
	 */
	public function testGivenRemoveChangeRequest_removesStatement( $changeRequest, EntityDocument $entity, $property ) {
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( $changeRequest );
		$changeOp->apply( $entity, new Summary() );

		$this->assertTrue( $entity->getStatements()->getByPropertyId( $property )->isEmpty() );
	}

	public function deleteStatementProvider() {
		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $property ) );
		$statement->setGuid( 'test-guid' );
		$entity = $this->getEntity();
		$entity->setStatements( new StatementList( [ $statement ] ) );

		return [
			'numeric index format' => [
				[ 'claims' => [
					[ 'remove' => '', 'id' => $statement->getGuid() ]
				] ],
				$entity,
				$property
			],
			'associative format' => [
				[ 'claims' => [
					'P7' => [ [ 'remove' => '', 'id' => $statement->getGuid() ] ]
				] ],
				$entity->copy(),
				$property
			],
		];
	}

	/**
	 * @dataProvider editStatementProvider
	 */
	public function testGivenEditChangeRequest_statementGetsChanged( $changeRequest, EntityDocument $entity ) {
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( $changeRequest );
		$changeOp->apply( $entity, new Summary() );

		$this->assertCount( 1, $entity->getStatements()->toArray() );
		$this->assertSame(
			'bar',
			$entity->getStatements()->toArray()[0]
				->getMainSnak()
				->getDataValue()
				->getValue()
		);
	}

	public function editStatementProvider() {
		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyValueSnak( $property, new StringValue( 'foo' ) ) );
		$entity = $this->getEntity();
		$statement->setGuid( ( new GuidGenerator() )->newGuid( $entity->getId() ) );
		$entity->setStatements( new StatementList( [ $statement ] ) );
		$statementSerialization = $this->getStatementSerializer()->serialize( $statement );
		$statementSerialization['mainsnak']['datavalue']['value'] = 'bar';

		return [
			'numeric index format' => [
				[ 'claims' => [ $statementSerialization ] ],
				$entity
			],
			'associative format' => [
				[ 'claims' => [ 'P7' => [ $statementSerialization ] ] ],
				$entity
			],
		];
	}

	private function getStatementSerializer() {
		return WikibaseRepo::getDefaultInstance()->getStatementSerializer();
	}

}
