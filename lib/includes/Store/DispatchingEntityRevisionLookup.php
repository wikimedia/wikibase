<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Assert\Assert as WikibaseAssert;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\EntityRevision;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Delegates lookup to the repository-specific EntityRevisionLookup
 * based on the name of the repository an EntityId belongs to.
 *
 * @license GPL-2.0+
 */
class DispatchingEntityRevisionLookup implements EntityRevisionLookup {
	/**
	 * @var EntityRevisionLookup[]
	 */
	private $lookups;

	/**
	 * @param EntityRevisionLookup[] $lookups associative array with repository names (strings) as keys
	 *                                        and EntityRevisionLookup objects as values. Empty-string
	 *                                        key defines lookup for the local repository.
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $lookups ) {
		Assert::parameter(
			!empty( $lookups ),
			'$lookups',
			'must must not be empty'
		);
		Assert::parameterElementType( EntityRevisionLookup::class, $lookups, '$lookups' );
		WikibaseAssert::parameterKeyIsAllowableRepositoryName( $lookups, '$lookups' );
		$this->lookups = $lookups;
	}

	/**
	 * @see   EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, or 0 for the latest revision.
	 * @param string $mode LATEST_FROM_SLAVE, LATEST_FROM_SLAVE_WITH_FALLBACK or
	 *        LATEST_FROM_MASTER.
	 *
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @throws UnknownForeignRepositoryException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_SLAVE
	) {
		$lookup = $this->getLookupForEntityId( $entityId );
		return $lookup->getEntityRevision( $entityId, $revisionId, $mode );
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 * @throws UnknownForeignRepositoryException
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE ) {
		$lookup = $this->getLookupForEntityId( $entityId );
		return $lookup->getLatestRevisionId( $entityId, $mode );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return EntityRevisionLookup
	 * @throws UnknownForeignRepositoryException
	 */
	private function getLookupForEntityId( EntityId $entityId ) {
		$repo = $entityId->getRepositoryName();
		if ( !isset( $this->lookups[$repo] ) ) {
			throw new UnknownForeignRepositoryException( $repo );
		}
		return $this->lookups[$repo];
	}

}
