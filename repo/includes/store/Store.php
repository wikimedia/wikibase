<?php

namespace Wikibase;

use Wikibase\Changes\ChangesTable;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\SiteLinkCache;
use Wikibase\Repo\Store\EntityPerPage;

/**
 * Store interface. All interaction with store Wikibase does on top
 * of storing pages and associated core MediaWiki indexing is done
 * through this interface.
 *
 * @todo: provide getXXX() methods for getting local pseudo-singletons (shared service objects).
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Store {

	/**
	 * @since 0.1
	 *
	 * @return SiteLinkCache
	 */
	public function newSiteLinkCache();

	/**
	 * Removes all data from the store.
	 *
	 * @since 0.1
	 */
	public function clear();

	/**
	 * Rebuilds the store from the original data source.
	 *
	 * @since 0.1
	 */
	public function rebuild();

	/**
	 * @since 0.4
	 *
	 * @return TermIndex
	 */
	public function getTermIndex();

	/**
	 * @since 0.5
	 *
	 * @return LabelConflictFinder
	 */
	public function getLabelConflictFinder();

	/**
	 * @since 0.1
	 *
	 * @return IdGenerator
	 */
	public function newIdGenerator();

	/**
	 * @since 0.3
	 *
	 * @return EntityPerPage
	 */
	public function newEntityPerPage();

	/**
	 * @since 0.4
	 *
	 * @param string $uncached Flag string, set to 'uncached' to get an uncached direct lookup service.
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup( $uncached = '' );

	/**
	 * @since 0.5
	 *
	 * @param string $uncached Flag string, set to 'uncached' to get an uncached direct lookup service.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup( $uncached = '' );

	/**
	 * @since 0.5
	 *
	 * @return EntityStore
	 */
	public function getEntityStore();

	/**
	 * Returns an EntityStoreWatcher that should be notified of changes to
	 * entities, in order to keep any caches updated.
	 *
	 * @since 0.5
	 *
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher();

	/**
	 * @since 0.5
	 *
	 * @return EntityInfoBuilderFactory
	 */
	public function getEntityInfoBuilderFactory();

	/**
	 * @since 0.4
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore();

	/**
	 * @since 0.5
	 *
	 * @return ChangesTable
	 */
	public function getChangesTable();

}
