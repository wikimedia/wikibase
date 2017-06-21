<?php

namespace Wikibase;

use LoggedUpdateMaintenance;
use Maintenance;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Sql\TermSqlIndexBuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RebuildTermSqlIndex extends Maintenance  {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuild the index in the wb terms table ' .
			'(among other things populating term_full_entity_id).' );

		$this->addOption(
			'batch-size', "Number of rows to update per batch (Default: 1000)",
			false,
			true
		);
		$this->addOption(
			'entity-type', "Only rebuild terms for specified entity type (e.g. 'item', 'property')",
			false,
			true
		);
		$this->addOption(
			'rebuild-all-terms', 'Rebuilds all terms of the entity (requires loading data of each processed entity)'
		);
		$this->addOption( 'from-id', "First row (page id) to start updating from", false, true );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->error( "You need to have Wikibase enabled in order to use this "
				. "maintenance script!\n\n", 1 );
		}

		$builder = $this->getTermIndexBuilder();
		$builder->rebuild();

		$this->output( "Done.\n" );
	}

	private function getTermIndexBuilder() {
		$batchSize = (int)$this->getOption( 'batch-size', 1000 );
		$fromId = $this->getOption( 'from-id', null );
		$rebuildAllEntityTerms = $this->getOption( 'rebuild-all-terms', false );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$idParser = $wikibaseRepo->getEntityIdParser();
		$entityIdComposer = $wikibaseRepo->getEntityIdComposer();
		$repoSettings = $wikibaseRepo->getSettings();

		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$idParser
		);

		$termIndex = $this->getTermSqlIndex(
			$entityIdComposer,
			$idParser,
			$repoSettings->getSetting( 'writeFullEntityIdColumn' ),
			$repoSettings->getSetting( 'readFullEntityIdColumn' )
		);

		$builder = new TermSqlIndexBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$termIndex,
			$sqlEntityIdPagerFactory,
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$this->getEntityTypes(),
			$this->getReporter(),
			$this->getErrorReporter(),
			$batchSize,
			$repoSettings->getSetting( 'readFullEntityIdColumn' )
		);

		if ( $fromId !== null ) {
			$builder->setFromId( (int)$fromId );
		}
		if ( $rebuildAllEntityTerms ) {
			$builder->setRebuildAllEntityTerms();
		}

		return $builder;
	}

	/**
	 * @return string[]
	 */
	private function getEntityTypes() {
		$entityType = $this->getOption( 'entity-type', null );
		$localEntityTypes = WikibaseRepo::getDefaultInstance()->getLocalEntityTypes();

		$entityTypes = $localEntityTypes;
		if ( $entityType !== null ) {
			if ( !in_array( $entityType, $localEntityTypes ) ) {
				$this->error( "Unknown entity type: \"$entityType\"\n", 1 );
			}
			$entityTypes = [ $entityType ];
		}

		return $entityTypes;
	}

	/**
	 * @param EntityIdComposer $entityIdComposer
	 * @param EntityIdParser $entityIdParser
	 * @param bool $writeFullEntityIdColumn
	 * @param bool $readFullEntityIdColumn
	 *
	 * @return TermSqlIndex
	 */
	private function getTermSqlIndex(
		EntityIdComposer $entityIdComposer,
		EntityIdParser $entityIdParser,
		$writeFullEntityIdColumn,
		$readFullEntityIdColumn
	) {
		$termSqlIndex = new TermSqlIndex(
			new StringNormalizer(),
			$entityIdComposer,
			$entityIdParser,
			false,
			'',
			$writeFullEntityIdColumn
		);

		$termSqlIndex->setReadFullEntityIdColumn( $readFullEntityIdColumn );

		return $termSqlIndex;
	}

	/**
	 * @return ObservableMessageReporter
	 */
	private function getReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $message ) {
			$this->output( "$message\n" );
		} );

		return $reporter;
	}

	/**
	 * @return ObservableMessageReporter
	 */
	private function getErrorReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $message ) {
			$this->error( "[ERROR] $message" );
		} );

		return $reporter;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\RebuildTermSqlIndex';
	}

}

$maintClass = RebuildTermSqlIndex::class;
require_once RUN_MAINTENANCE_IF_MAIN;
