<?php

declare( strict_types=1 );

namespace Wikibase\Repo\FederatedProperties;

use InvalidArgumentException;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;

/**
 * A class to initialize default entitySource values for federated properties
 *
 * @license GPL-2.0-or-later
 *
 * @author Tobias Andersson
 */
class FederatedPropertiesEntitySourceDefinitionsConfigParser {

	/**
	 * @var string
	 */
	private $fedPropsSource;

	/**
	 * @var string
	 */
	private $localEntitySourceName;

	public function __construct( SettingsArray $settings ) {
		$this->fedPropsSource = $settings->getSetting( 'federatedPropertiesSourceScriptUrl' );
		$this->localEntitySourceName = $settings->getSetting( 'localEntitySourceName' );
	}

	/**
	 * @param EntitySource[] $sources
	 * @return EntitySource
	 */
	private function getLocalEntitySource( array $sources ) : EntitySource {
		$result = array_filter(
			$sources,
			function ( $e ) {
				return $e->getSourceName() === $this->localEntitySourceName;
			}
		);

		if ( empty( $result ) ) {
			throw new InvalidArgumentException( 'No entity sources defined for "' . $this->localEntitySourceName . '"' );
		}

		return $result[0];
	}

	/**
	 * @param EntitySourceDefinitions $definitions
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 * @return EntitySourceDefinitions
	 */
	public function initializeDefaults( EntitySourceDefinitions $definitions, EntityTypeDefinitions $entityTypeDefinitions ) {

		if ( $this->fedPropsSource !== 'https://www.wikidata.org/w/' ) {
			return $definitions;
		}

		$definitions->getSources();
		$defaultLocal = $this->getLocalEntitySource( $definitions->getSources() );

		$entityTypes = $defaultLocal->getEntityTypes();
		$entityNamespaceIds = $defaultLocal->getEntityNamespaceIds();
		$entitySlots = $defaultLocal->getEntitySlotNames();

		$propertyIndex = array_search( Property::ENTITY_TYPE, $entityTypes );

		$entityNamespaceIdsAndSlots = [];
		foreach ( $entityTypes as $entityType ) {
			$entityNamespaceIdsAndSlots[$entityType] = [
				'namespaceId' => $entityNamespaceIds[$entityType],
				'slot' => $entitySlots[$entityType]
			];
		}

		$propertyNamespaceIdAndSlot = $entityNamespaceIdsAndSlots[Property::ENTITY_TYPE];
		unset( $entityNamespaceIdsAndSlots[ Property::ENTITY_TYPE] );

		$newLocal = new EntitySource(
			$defaultLocal->getSourceName(),
			$defaultLocal->getDatabaseName(),
			$entityNamespaceIdsAndSlots,
			$defaultLocal->getConceptBaseUri(),
			$defaultLocal->getRdfNodeNamespacePrefix(),
			$defaultLocal->getRdfPredicateNamespacePrefix(),
			$defaultLocal->getInterwikiPrefix()
		);

		$fedPropsSource = new EntitySource(
			'fedprops',
			false,
			[ Property::ENTITY_TYPE => $propertyNamespaceIdAndSlot ],
			'http://www.wikidata.org/entity/',
			'fpwd',
			'fpwd',
			'wd'
		);

		return new EntitySourceDefinitions( [ $newLocal, $fedPropsSource ], $entityTypeDefinitions );
	}
}
