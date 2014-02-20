<?php

namespace Wikibase;

use DatabaseBase;
use DatabaseUpdater;
use DBQueryError;
use MWException;
use ObjectCache;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\WikibaseRepo;

/**
 * Implementation of the store interface using an SQL backend via MediaWiki's
 * storage abstraction layer.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SqlStore implements Store {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup = null;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup = null;

	/**
	 * @var EntityInfoBuilder
	 */
	private $entityInfoBuilder = null;

	/**
	 * @var PropertyInfoTable
	 */
	private $propertyInfoTable = null;

	/**
	 * @var TermIndex
	 */
	private $termIndex = null;

	/**
	 * @var string
	 */
	private $cachePrefix;

	/**
	 * @var int
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	public function __construct() {
		//NOTE: once I59e8423c is in, we no longer need the singleton.
		$settings = Settings::singleton();
		$cachePrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$cacheType = $settings->getSetting( 'sharedCacheType' );

		$this->cachePrefix = $cachePrefix;
		$this->cacheDuration = $cacheDuration;
		$this->cacheType = $cacheType;
	}

	/**
	 * @see Store::getTermIndex
	 *
	 * @since 0.4
	 *
	 * @return TermIndex
	 */
	public function getTermIndex() {
		if ( !$this->termIndex ) {
			$this->termIndex = $this->newTermIndex();
		}

		return $this->termIndex;
	}

	/**
	 * @since 0.1
	 *
	 * @return TermIndex
	 */
	protected function newTermIndex() {
		//TODO: Get $stringNormalizer from WikibaseRepo?
		//      Can't really pass this via the constructor...
		$stringNormalizer = new StringNormalizer();
		return new TermSqlIndex( $stringNormalizer );
	}

	/**
	 * @see Store::clear
	 *
	 * @since 0.1
	 */
	public function clear() {
		$this->newSiteLinkCache()->clear();
		$this->getTermIndex()->clear();
		$this->newEntityPerPage()->clear();
	}

	/**
	 * @see Store::rebuild
	 *
	 * @since 0.1
	 */
	public function rebuild() {
		$dbw = wfGetDB( DB_MASTER );

		// TODO: refactor selection code out (relevant for other stores)

		$pages = $dbw->select(
			array( 'page' ),
			array( 'page_id', 'page_latest' ),
			array( 'page_content_model' => WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getEntityContentModels() ),
			__METHOD__,
			array( 'LIMIT' => 1000 ) // TODO: continuation
		);

		foreach ( $pages as $pageRow ) {
			$page = \WikiPage::newFromID( $pageRow->page_id );
			$revision = \Revision::newFromId( $pageRow->page_latest );
			try {
				$page->doEditUpdates( $revision, $GLOBALS['wgUser'] );
			} catch ( DBQueryError $e ) {
				wfLogWarning(
					'editUpdateFailed for ' . $page->getId() . ' on revision ' .
					$revision->getId() . ': ' . $e->getMessage()
				);
			}
		}
	}

	/**
	 * Updates the schema of the SQL store to it's latest version.
	 *
	 * @since 0.1
	 *
	 * @param DatabaseUpdater $updater
	 */
	public function doSchemaUpdate( DatabaseUpdater $updater ) {
		$db = $updater->getDB();

		// Update from 0.1.
		if ( !$db->tableExists( 'wb_terms' ) ) {
			$updater->dropTable( 'wb_items_per_site' );
			$updater->dropTable( 'wb_items' );
			$updater->dropTable( 'wb_aliases' );
			$updater->dropTable( 'wb_texts_per_lang' );

			$updater->addExtensionTable(
				'wb_terms',
				$this->getUpdateScriptPath( 'Wikibase', $db->getType() )
			);

			$this->rebuild();
		}

		$this->updateEntityPerPageTable( $updater, $db );
		$this->updateTermsTable( $updater, $db );

		PropertyInfoTable::registerDatabaseUpdates( $updater );
	}

	/**
	 * Returns the script directory that contains a file with the given name.
	 *
	 * @param string $fileName with extension
	 *
	 * @throws MWException If the file was not found in any script directory
	 * @return string The directory that contains the file
	 */
	private function getUpdateScriptDir( $fileName ) {
		$dirs = array(
			__DIR__,
			__DIR__ . '/../../../sql'
		);

		foreach ( $dirs as $dir ) {
			if ( file_exists( "$dir/$fileName" ) ) {
				return $dir;
			}
		}

		throw new MWException( "Update script not found: $fileName" );
	}

	/**
	 * Returns the appropriate script file for use with the given database type.
	 * Searches for files with type-specific extensions in the script directories,
	 * falling back to the plain ".sql" extension if no specific script is found.
	 *
	 * @param string $name the script's name, without file extension
	 * @param string $type the database type, as returned by DatabaseBase::getType()
	 *
	 * @return string The path to the script file
	 * @throws MWException If the script was not found in any script directory
	 */
	private function getUpdateScriptPath( $name, $type ) {
		$extensions = array(
			'sqlite' => 'sqlite.sql',
			//'postgres' => 'pg.sql', // PG support is broken as of Dec 2013
			'mysql' => 'mysql.sql',
		);

		// Find the base directory by looking for a plain ".sql" file.
		$dir = $this->getUpdateScriptDir( "$name.sql" );

		if ( isset( $extensions[$type] ) ) {
			$extension = $extensions[$type];
			$path = "$dir/$name.$extension";

			// if a type-specific file exists, use it
			if ( file_exists( "$dir/$name.$extension" ) ) {
				return $path;
			}
		} else {
			throw new MWException( "Database type $type is not supported by Wikibase!" );
		}

		// we already know that the generic file exists
		$path = "$dir/$name.sql";
		return $path;
	}

	/**
	 * Applies updates to the wb_entity_per_page table.
	 *
	 * @param DatabaseUpdater $updater
	 * @param DatabaseBase $db
	 */
	private function updateEntityPerPageTable( DatabaseUpdater $updater, DatabaseBase $db ) {
		// Update from 0.1. or 0.2.
		if ( !$db->tableExists( 'wb_entity_per_page' ) ) {

			$updater->addExtensionTable(
				'wb_entity_per_page',
				$this->getUpdateScriptPath( 'AddEntityPerPage', $db->getType() )
			);

			$updater->addPostDatabaseUpdateMaintenance( 'Wikibase\RebuildEntityPerPage' );
		}
	}

	/**
	 * Applies updates to the wb_terms table.
	 *
	 * @param DatabaseUpdater $updater
	 * @param DatabaseBase $db
	 */
	private function updateTermsTable( DatabaseUpdater $updater, DatabaseBase $db ) {

		// ---- Update from 0.1 or 0.2. ----
		if ( !$db->fieldExists( 'wb_terms', 'term_search_key' ) &&
			!Settings::get( 'withoutTermSearchKey' ) ) {

			$updater->addExtensionField(
				'wb_terms',
				'term_search_key',
				$this->getUpdateScriptPath( 'AddTermsSearchKey', $db->getType() )
			);

			$updater->addPostDatabaseUpdateMaintenance( 'Wikibase\RebuildTermsSearchKey' );
		}

		// creates wb_terms.term_row_id
		// and also wb_item_per_site.ips_row_id.
		$updater->addExtensionField(
			'wb_terms',
			'term_row_id',
			$this->getUpdateScriptPath( 'AddRowIDs', $db->getType() )
		);

		// add weight to wb_terms
		$updater->addExtensionField(
			'wb_terms',
			'term_weight',
			$this->getUpdateScriptPath( 'AddTermsWeight', $db->getType() )
		);

		// ---- Update from 0.4 ----
		// drop obsolete indexes
		$updater->dropExtensionIndex(
			'wb_terms',
			'wb_terms_entity_id',
			$this->getUpdateScriptPath( 'DropTermIndexes04', $db->getType() )
		);

		// NOTE: mysqli reports field type encoded as an int, see <http://php.net/manual/en/mysqli-result.fetch-field-direct.php#89117>.
		//       253 is "varchar".
		$entityIdTypes = array( 'text', 'varchar', 253 );

		if ( !Settings::get( 'useNumericIdsInTermsTable' ) ) {

			// Drop current indexes, so they don't get in the way when converting.
			// NOTE: redundant, but only until we can actually check the field type above.
			$updater->dropExtensionIndex(
				'wb_terms',
				'term_search',
				$this->getUpdateScriptPath( 'DropTermIndexes', $db->getType() )
			);

			// make term_entity_id a VARCHAR and populate it with prefixed IDs
			// (recorded in updatelog, so it's only done once)
			$updater->addExtensionField(
				'wb_terms',
				'term_full_entity_id',
				$this->getUpdateScriptPath( 'MigrateTermEntityId', $db->getType() )
			);

			if ( $db->getType() === 'mysql' ) {
				$updater->dropExtensionField(
					'wb_terms',
					'term_entity_id',
					$this->getUpdateScriptPath( 'DropObsoleteEntityidFieldInTermsTable', $db->getType() )
				);
			}
			// indexes get re-created below
		}

		// NOTE: mysqli reports field type encoded as an int, see <http://php.net/manual/en/mysqli-result.fetch-field-direct.php#89117>.
		//       253 is "varchar".
		$rowIdTypes = array( 'bigint', 8 );

		if ( $db->getType() === 'sqlite' ) {
			// sqlite's integer type can grow to 8 bytes
			$rowIdTypes[] = 'integer';
		}

		if ( !$this->checkFieldType( $db, 'wb_terms', 'term_row_id', $rowIdTypes ) ) {

			// make term_row_id BIGINT (recorded in updatelog, so it's only done once)
			$updater->modifyExtensionField(
				'wb_terms',
				'term_row_id',
				$this->getUpdateScriptPath( 'MakeRowIDsBig', $db->getType() )
			);
		}

		// (re)create missing indexes
		$updater->addExtensionIndex(
			'wb_terms',
			'term_search',
			$this->getUpdateScriptPath( 'CreateTermIndexes', $db->getType() )
		);
	}

	/**
	 * Checks the type of a database field against a list of types.
	 * This hides some of the awkwardness of checking field type.
	 *
	 * @param DatabaseBase $db
	 * @param string $table
	 * @param string $field
	 * @param array $types
	 *
	 * @return bool true iff the actual type of the field is one of the types listed in $types.
	 */
	private function checkFieldType( DatabaseBase $db, $table, $field, $types ) {
		$fieldInfo = $db->fieldInfo( $table, $field );
		$fieldType = $fieldInfo ? $fieldInfo->type() : false;

		return in_array( strtolower( $fieldType ), $types );
	}

	/**
	 * @see Store::newIdGenerator
	 *
	 * @since 0.1
	 *
	 * @return IdGenerator
	 */
	public function newIdGenerator() {
		return new SqlIdGenerator( 'wb_id_counters', wfGetDB( DB_MASTER ) );
	}

	/**
	 * @see Store::newSiteLinkCache
	 *
	 * @since 0.1
	 *
	 * @return SiteLinkCache
	 */
	public function newSiteLinkCache() {
		return new SiteLinkTable( 'wb_items_per_site', false );
	}

	/**
	 * @see Store::newEntityPerPage
	 *
	 * @since 0.3
	 *
	 * @return EntityPerPage
	 */
	public function newEntityPerPage() {
		return new EntityPerPageTable();
	}

	/**
	 * @see Store::getEntityLookup
	 *
	 * @since 0.4
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup() {
		if ( !$this->entityLookup ) {
			$this->entityLookup = $this->newEntityLookup();
		}

		return $this->entityLookup;
	}

	/**
	 * Creates a new EntityLookup
	 *
	 * @return EntityLookup
	 */
	protected function newEntityLookup() {
		//NOTE: two layers of caching: persistent external cache in WikiPageEntityLookup;
		//      transient local cache in CachingEntityLoader.
		//NOTE: Keep in sync with DirectSqlStore::newEntityLookup on the client
		$key = $this->cachePrefix . ':WikiPageEntityLookup';
		$lookup = new WikiPageEntityLookup( false, $this->cacheType, $this->cacheDuration, $key );
		return new CachingEntityLoader( $lookup );
	}

	/**
	 * @see Store::getEntityRevisionLookup
	 *
	 * @since 0.4
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		if ( !$this->entityRevisionLookup ) {
			$this->entityRevisionLookup = $this->newEntityRevisionLookup();
		}

		return $this->entityRevisionLookup;
	}

	/**
	 * Creates a new EntityRevisionLookup
	 *
	 * @return EntityRevisionLookup
	 */
	protected function newEntityRevisionLookup() {
		//TODO: implement CachingEntityLoader based on EntityRevisionLookup instead of
		//      EntityLookup. Then we can layer an EntityLookup on top of that.
		$key = $this->cachePrefix . ':WikiPageEntityLookup';
		$lookup = new WikiPageEntityLookup( false, $this->cacheType, $this->cacheDuration, $key );
		return $lookup;
	}

	/**
	 * @see Store::getEntityInfoBuilder
	 *
	 * @since 0.4
	 *
	 * @return EntityInfoBuilder
	 */
	public function getEntityInfoBuilder() {
		if ( !$this->entityInfoBuilder ) {
			$this->entityInfoBuilder = $this->newEntityInfoBuilder();
		}

		return $this->entityInfoBuilder;
	}

	/**
	 * Creates a new EntityInfoBuilder
	 *
	 * @return EntityInfoBuilder
	 */
	protected function newEntityInfoBuilder() {
		//TODO: Get $idParser from WikibaseRepo?
		$idParser = new BasicEntityIdParser();
		$builder = new SqlEntityInfoBuilder( $idParser );
		return $builder;
	}

	/**
	 * @see Store::getPropertyInfoStore
	 *
	 * @since 0.4
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( !$this->propertyInfoTable ) {
			$this->propertyInfoTable = $this->newPropertyInfoTable();
		}

		return $this->propertyInfoTable;
	}

	/**
	 * Creates a new PropertyInfoTable
	 *
	 * @return PropertyInfoTable
	 */
	protected function newPropertyInfoTable() {
		if ( Settings::get( 'usePropertyInfoTable' ) ) {
			$table = new PropertyInfoTable( false );
			$key = $this->cachePrefix . ':CachingPropertyInfoStore';
			return new CachingPropertyInfoStore( $table, ObjectCache::getInstance( $this->cacheType ),
				$this->cacheDuration, $key );
		} else {
			// dummy info store
			return new DummyPropertyInfoStore();
		}
	}

}
