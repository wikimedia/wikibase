<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql;

use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\DBQueryError;

/**
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SqlChangeStore implements ChangeStore {

	/**
	 * @var RepoDomainDb
	 */
	private $repoDomainDb;

	public function __construct( RepoDomainDb $repoDomainDb ) {
		$this->repoDomainDb = $repoDomainDb;
	}

	/**
	 * Saves the change to a database table.
	 *
	 * @note Only supports Change objects that are derived from ChangeRow.
	 *
	 * @param Change $change
	 *
	 * @throws DBQueryError
	 */
	public function saveChange( Change $change ) {
		Assert::parameterType( ChangeRow::class, $change, '$change' );
		'@phan-var ChangeRow $change';

		if ( $change->getId() === null ) {
			$this->insertChange( $change );
		} else {
			$this->updateChange( $change );
		}
	}

	private function updateChange( ChangeRow $change ) {
		$values = $this->getValues( $change );

		$dbw = $this->repoDomainDb->connections()->getWriteConnection();

		$dbw->update(
			'wb_changes',
			$values,
			[ 'change_id' => $change->getId() ],
			__METHOD__
		);

		$this->repoDomainDb->connections()->releaseConnection( $dbw );
	}

	private function insertChange( ChangeRow $change ) {
		$values = $this->getValues( $change );

		$dbw = $this->repoDomainDb->connections()->getWriteConnection();

		$dbw->insert( 'wb_changes', $values, __METHOD__ );
		$change->setField( 'id', $dbw->insertId() );

		$this->repoDomainDb->connections()->releaseConnection( $dbw );
	}

	/**
	 * @param ChangeRow $change
	 *
	 * @return array
	 */
	private function getValues( ChangeRow $change ) {
		$type = $change->getType();
		// TODO: Avoid depending on hasField here.
		$time = $change->hasField( 'time' ) ? $change->getTime() : wfTimestampNow();
		$objectId = $change->hasField( 'object_id' ) ? $change->getObjectId() : '';
		// TODO: Introduce dedicated getter for revision ID.
		$revisionId = $change->hasField( 'revision_id' ) ? $change->getField( 'revision_id' ) : '0';
		$userId = $change->getUserId();
		$serializedInfo = $change->getSerializedInfo();

		return [
			'change_type' => $type,
			'change_time' => $time,
			'change_object_id' => $objectId,
			'change_revision_id' => $revisionId,
			'change_user_id' => $userId,
			'change_info' => $serializedInfo,
		];
	}

}
