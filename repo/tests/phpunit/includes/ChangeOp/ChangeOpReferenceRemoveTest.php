<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\ChangeOp\ChangeOpReferenceRemove;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ChangeOp\ChangeOpReferenceRemove
 *
 * @since 0.5
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpReferenceRemoveTest extends \PHPUnit_Framework_TestCase {

	public function changeOpRemoveProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q345', $snak );
		$claims = $item->getClaims();
		/** @var Statement $claim */
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$references = $claim->getReferences();
		$references->addReference( $newReference );
		$claim->setReferences( $references );
		$item->setClaims( new Claims( $claims ) );
		$referenceHash = $newReference->getHash();
		$changeOp = new ChangeOpReferenceRemove( $claimGuid, $referenceHash );
		$args[] = array ( $item, $changeOp, $referenceHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpReferenceRemove $changeOp
	 * @param string $referenceHash
	 */
	public function testApplyRemoveReference( $item, $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = $item->getClaims();
		/** @var Statement $claim */
		$claim = reset( $claims );
		$references = $claim->getReferences();
		$this->assertFalse( $references->hasReferenceHash( $referenceHash ), "Reference still exists" );
	}

	protected function provideNewItemWithClaim( $itemId, $snak ) {
		$entity = ItemContent::newFromArray( array( 'entity' => $itemId ) )->getEntity();
		$claim = $entity->newClaim( $snak );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8494TYA-25E4-4334-AG03-A3290BCT9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}

} 