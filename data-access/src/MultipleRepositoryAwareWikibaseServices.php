<?php


namespace Wikibase\DataAccess;

use MediaWiki\Services\ServiceContainer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\StringNormalizer;

/**
 * Top-level container/factory of data access services making use of the "dispatching" pattern of
 * services aware of multi-repository configuration that delegate their action
 * to service instance configured for a particular repository.
 *
 * @license GPL-2.0+
 */
class MultipleRepositoryAwareWikibaseServices extends ServiceContainer implements WikibaseServices {

	/**
	 * @param EntityIdParser $idParser
	 * @param EntityIdComposer $idComposer
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param RepositoryDefinitions $repositoryDefinitions
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 * @param DataAccessSettings $settings
	 * @param callable[] $multiRepositoryServiceWiring
	 * @param callable[] $perRepositoryServiceWiring
	 */
	public function __construct(
		EntityIdParser $idParser,
		EntityIdComposer $idComposer,
		EntityNamespaceLookup $entityNamespaceLookup,
		RepositoryDefinitions $repositoryDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		DataAccessSettings $settings,
		array $multiRepositoryServiceWiring,
		array $perRepositoryServiceWiring
	) {
		parent::__construct();

		$genericServices = new GenericServices( $entityNamespaceLookup, $entityTypeDefinitions );

		$multiRepositoryServices = $this->getMultiRepositoryServices(
			$idParser,
			$idComposer,
			$repositoryDefinitions,
			$entityTypeDefinitions,
			$genericServices,
			$settings,
			$perRepositoryServiceWiring

		);
		$multiRepositoryServices->applyWiring( $multiRepositoryServiceWiring );

		$this->defineServices( $multiRepositoryServices, $genericServices );
	}

	private function getMultiRepositoryServices(
		EntityIdParser $idParser,
		EntityIdComposer $idComposer,
		RepositoryDefinitions $repositoryDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		GenericServices $genericServices,
		DataAccessSettings $settings,
		array $perRepositoryServiceWiring
	) {
		return new MultiRepositoryServices(
			$this->getRepositoryServiceContainerFactory(
				$idParser,
				$idComposer,
				$repositoryDefinitions,
				$entityTypeDefinitions,
				$genericServices,
				$settings,
				$perRepositoryServiceWiring
			),
			$repositoryDefinitions
		);
	}

	private function getRepositoryServiceContainerFactory(
		EntityIdParser $idParser,
		EntityIdComposer $idComposer,
		RepositoryDefinitions $repositoryDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		GenericServices $genericServices,
		DataAccessSettings $settings,
		array $perRepositoryServiceWiring
	) {
		$idParserFactory = new PrefixMappingEntityIdParserFactory(
			$idParser,
			$repositoryDefinitions->getPrefixMappings()
		);

		return new RepositoryServiceContainerFactory(
			$idParserFactory,
			$idComposer,
			new RepositorySpecificDataValueDeserializerFactory( $idParserFactory ),
			$repositoryDefinitions->getDatabaseNames(),
			$perRepositoryServiceWiring,
			$genericServices,
			$settings,
			$entityTypeDefinitions
		);
	}

	private function defineServices( MultiRepositoryServices $multiRepositoryServices, GenericServices $genericServices ) {
		$this->applyWiring( [
			'EntityInfoBuilderFactory' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getEntityInfoBuilderFactory();
			},
			'EntityPrefetcher' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getEntityPrefetcher();
			},
			'EntityRevisionLookup' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getEntityRevisionLookup();
			},
			'EntityStoreWatcher' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices;
			},
			'LanguageFallbackChainFactory' => function () use ( $genericServices ) {
				return $genericServices->getLanguageFallbackChainFactory();
			},
			'PropertyInfoLookup' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getPropertyInfoLookup();
			},
			'StringNormalizer' => function() use ( $genericServices ) {
				return $genericServices->getStringNormalizer();
			},
			'TermBuffer' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getTermBuffer();
			},
			'TermSearchInteractorFactory' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getTermSearchInteractorFactory();
			},
		] );
	}

	/**
	 * @return EntityInfoBuilderFactory
	 */
	public function getEntityInfoBuilderFactory() {
		return $this->getService( 'EntityInfoBuilderFactory' );
	}

	/**
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher() {
		return $this->getService( 'EntityPrefetcher' );
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->getService( 'EntityRevisionLookup' );
	}

	/**
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher() {
		return $this->getService( 'EntityStoreWatcher' );
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		return $this->getService( 'LanguageFallbackChainFactory' );
	}

	/**
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup() {
		return $this->getService( 'PropertyInfoLookup' );
	}

	/**
	 * @return StringNormalizer
	 */
	public function getStringNormalizer() {
		return $this->getService( 'StringNormalizer' );
	}

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		return $this->getService( 'TermBuffer' );
	}

	/**
	 * @return TermSearchInteractorFactory
	 */
	public function getTermSearchInteractorFactory() {
		return $this->getService( 'TermSearchInteractorFactory' );
	}

}
