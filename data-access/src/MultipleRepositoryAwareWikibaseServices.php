<?php


namespace Wikibase\DataAccess;

use MediaWiki\Services\ServiceContainer;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\SerializerFactory;
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
	 * @var MultiRepositoryServices
	 */
	private $multiRepositoryServices;

	/**
	 * @var GenericServices
	 */
	private $genericServices;

	/**
	 * @param EntityIdParser $idParser
	 * @param EntityIdComposer $idComposer
	 * @param RepositoryDefinitions $repositoryDefinitions
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 * @param DataAccessSettings $settings
	 * @param callable[] $multiRepositoryServiceWiring
	 * @param callable[] $perRepositoryServiceWiring
	 */
	public function __construct(
		EntityIdParser $idParser,
		EntityIdComposer $idComposer,
		RepositoryDefinitions $repositoryDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		DataAccessSettings $settings,
		array $multiRepositoryServiceWiring,
		array $perRepositoryServiceWiring
	) {
		parent::__construct();

		$this->genericServices = new GenericServices(
			$entityTypeDefinitions,
			$repositoryDefinitions->getEntityNamespaces()
		);

		$this->multiRepositoryServices = $this->createMultiRepositoryServices(
			$idParser,
			$idComposer,
			$repositoryDefinitions,
			$entityTypeDefinitions,
			$settings,
			$perRepositoryServiceWiring

		);
		$this->multiRepositoryServices->applyWiring( $multiRepositoryServiceWiring );

		$this->defineServices();
	}

	private function createMultiRepositoryServices(
		EntityIdParser $idParser,
		EntityIdComposer $idComposer,
		RepositoryDefinitions $repositoryDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		DataAccessSettings $settings,
		array $perRepositoryServiceWiring
	) {
		return new MultiRepositoryServices(
			$this->getRepositoryServiceContainerFactory(
				$idParser,
				$idComposer,
				$repositoryDefinitions,
				$entityTypeDefinitions,
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
		DataAccessSettings $settings,
		array $perRepositoryServiceWiring
	) {
		$idParserFactory = new PrefixMappingEntityIdParserFactory(
			$idParser,
			$repositoryDefinitions->getPrefixMappings()
		);

		return new PerRepositoryServiceContainerFactory(
			$idParserFactory,
			$idComposer,
			new RepositorySpecificDataValueDeserializerFactory( $idParserFactory ),
			$repositoryDefinitions->getDatabaseNames(),
			$perRepositoryServiceWiring,
			$this->genericServices,
			$settings,
			$entityTypeDefinitions
		);
	}

	private function defineServices() {
		$multiRepositoryServices = $this->multiRepositoryServices;
		$genericServices = $this->genericServices;

		$this->applyWiring( [
			'EntityInfoBuilderFactory' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getEntityInfoBuilderFactory();
			},
			'EntityNamespaceLookup' => function() use ( $genericServices ) {
				return $genericServices->getEntityNamespaceLookup();
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
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup() {
		return $this->getService( 'EntityNamespaceLookup' );
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
	 * Returns the entity serializer instance that includes snak hashes in the serialization.
	 *
	 * @return Serializer
	 */
	public function getEntitySerializer() {
		return $this->genericServices->getEntitySerializer();
	}

	/**
	 * Returns the entity serializer instance that omits snak hashes in the serialization.
	 *
	 * @return Serializer
	 */
	public function getCompactEntitySerializer() {
		return $this->genericServices->getCompactEntitySerializer();
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
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types. Snak hashes are included in the serialization.
	 */
	public function getSerializerFactory() {
		return $this->genericServices->getSerializerFactory();
	}

	/**
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types. Snak hashes are omitted in the serialization.
	 */
	public function getCompactSerializerFactory() {
		return $this->genericServices->getCompactSerializerFactory();
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

	public function getMultiRepositoryServices() {
		return $this->multiRepositoryServices;
	}

}
