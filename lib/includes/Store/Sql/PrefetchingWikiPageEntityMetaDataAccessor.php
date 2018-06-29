<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use MapCacheLRU;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * A WikiPageEntityMetaDataAccessor decorator that implements prefetching and caching.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class PrefetchingWikiPageEntityMetaDataAccessor implements EntityPrefetcher, EntityStoreWatcher,
		WikiPageEntityMetaDataAccessor {

	/**
	 * @var WikiPageEntityMetaDataAccessor
	 */
	private $metaDataAccessor;

	/**
	 * @var int
	 */
	private $maxCacheKeys;

	/**
	 * @var MapCacheLRU
	 */
	private $cache;

	/**
	 * @var EntityId[]
	 */
	private $toFetch = [];

	/**
	 * @param WikiPageEntityMetaDataAccessor $metaDataAccessor
	 * @param int $maxCacheKeys Maximum number of entries to cache (defaults to 1000)
	 */
	public function __construct( WikiPageEntityMetaDataAccessor $metaDataAccessor, $maxCacheKeys = 1000 ) {
		$this->metaDataAccessor = $metaDataAccessor;
		$this->maxCacheKeys = $maxCacheKeys;
		$this->cache = new MapCacheLRU( $maxCacheKeys );
	}

	/**
	 * Marks the given entity ids for prefetching.
	 *
	 * @param EntityId[] $entityIds
	 */
	public function prefetch( array $entityIds ) {
		$entityIdCount = count( $entityIds );

		if ( $entityIdCount > $this->maxCacheKeys ) {
			// Ouch... fetching everything wouldn't fit into the cache, thus
			// other functions might not find what they're looking for.
			// Create a new, large enough MapCacheLRU to mitigate this.
			$this->cache = new MapCacheLRU( $entityIdCount + 1 );
			$this->maxCacheKeys = $entityIdCount + 1;
			wfDebugLog(
				'PrefetchingWikiPageEntityMetaDataAccessor',
				"Needed to create a new MapCacheLRU instance for $entityIdCount entities."
			);
		}
		if ( ( $entityIdCount + count( $this->toFetch ) ) > $this->maxCacheKeys ) {
			// Fetching everything would exceed the capacity of the cache,
			// thus discard all older entity ids as we can safely ignore these.
			$this->toFetch = [];
		}

		foreach ( $entityIds as $entityId ) {
			$idSerialization = $entityId->getSerialization();
			if ( $this->cache->has( $idSerialization ) ) {
				// Make sure the entities we already know about are not going
				// to be purged, by requesting them.
				$this->cache->get( $idSerialization );
			} else {
				$this->toFetch[$idSerialization] = $entityId;
			}
		}
	}

	/**
	 * @see EntityPrefetcher::purge
	 *
	 * @param EntityId $entityId
	 */
	public function purge( EntityId $entityId ) {
		$this->cache->clear( $entityId->getSerialization() );
	}

	/**
	 * @see EntityPrefetcher::purgeAll
	 */
	public function purgeAll() {
		$this->cache->clear();
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$this->purge( $entityId );
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$this->purge( $entityRevision->getEntity()->getId() );
	}

	/**
	 * @see EntityStoreWatcher::redirectUpdated
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		$this->purge( $entityRedirect->getEntityId() );
	}

	/**
	 * @see WikiPageEntityMetaDataAccessor::loadRevisionInformation
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_REPLICA,
	 *     EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @return (stdClass|bool)[] Array mapping entity ID serializations to either objects
	 * or false if an entity could not be found.
	 */
	public function loadRevisionInformation( array $entityIds, $mode ) {
		if ( $mode === EntityRevisionLookup::LATEST_FROM_MASTER ) {
			// Don't attempt to use the cache in case we are asked to fetch
			// from master. Also don't put load on the master by just fetching
			// everything in $this->toFetch.
			$data = $this->metaDataAccessor->loadRevisionInformation( $entityIds, $mode );
			// Cache the data, just in case it will be needed again.
			$this->store( $data );
			// Make sure we wont fetch these next time.
			foreach ( $entityIds as $entityId ) {
				$key = $entityId->getSerialization();
				unset( $this->toFetch[$key] );
			}

			return $data;
		}

		$this->prefetch( $entityIds );
		$this->doFetch( $mode );

		$data = [];
		foreach ( $entityIds as $entityId ) {
			$data[$entityId->getSerialization()] = $this->cache->get( $entityId->getSerialization() );
		}

		return $data;
	}

	/**
	 * @see WikiPageEntityMetaDataAccessor::loadLatestRevisionIds
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_REPLICA,
	 *     EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @return (int|bool)[] Array of entity ID serialization => revision IDs
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	public function loadLatestRevisionIds( array $entityIds, $mode ) {
		$this->doFetch( $mode );

		$revisionIds = [];

		if ( $mode !== EntityRevisionLookup::LATEST_FROM_MASTER ) {
			foreach ( $entityIds as $index => $entityId ) {
				$id = $entityId->getSerialization();
				$data = $this->cache->get( $id );
				if ( $data !== null ) {
					if ( $data->page_is_redirect ) {
						$revisionIds[$id] = false;
					} else {
						$revisionIds[$id] = $data->page_latest;
					}
					unset( $entityIds[$index] );
				}
			}
		}

		if ( $entityIds !== [] ) {
			$revisionIds = array_merge(
				$revisionIds,
				$this->metaDataAccessor->loadLatestRevisionIds( array_values( $entityIds ), $mode )
			);
			// no caching for these – would require a separate cache, not worth it
		}

		return $revisionIds;
	}

	private function doFetch( $mode ) {
		if ( empty( $this->toFetch ) ) {
			return;
		}

		try {
			$data = $this->metaDataAccessor->loadRevisionInformation( $this->toFetch, $mode );
		} catch ( InvalidArgumentException $exception ) {
			// Do not store invalid entity ids (causing exceptions in lookup).

			// TODO: if the $exception was of more specific type and provided the relevant entity id,
			// it would possible to only remove the relevant key from toFetch.
			$this->toFetch = [];

			// Re-throw the exception to be handled by caller.
			throw $exception;
		}

		// Store the data, including cache misses
		$this->store( $data );

		// Prune $this->toFetch
		$this->toFetch = [];
	}

	private function store( array $data ) {
		foreach ( $data as $key => $value ) {
			$this->cache->set( $key, $value );
		}
	}

}
