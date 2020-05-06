<?php


namespace Wikibase\Repo\FederatedProperties;

use LogicException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class ApiEntityLookup {

	/**
	 * @var GenericActionApiClient
	 */
	private $api;

	private $entityLookupResult = [];

	public function __construct( GenericActionApiClient $api ) {
		$this->api = $api;
	}

	public function fetchEntities( array $entityIds ): void {
		$response = $this->api->get( [
			'action' => 'wbgetentities',
			'ids' => implode( '|', $entityIds ),
			'props' => 'labels|descriptions|datatype',
			'format' => 'json'
		] );

		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable The API response will be JSON here
		$this->entityLookupResult = json_decode( $response->getBody()->getContents(), true )[ 'entities' ];
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return array containing the part of the wbgetentities response for the given entity id
	 */
	public function getResultPartForId( EntityId $entityId ): array {
		if ( !array_key_exists( $entityId->getSerialization(), $this->entityLookupResult ) ) {
			throw new LogicException( 'Trying to get data from an entity that was not fetched' );
		}

		return $this->entityLookupResult[ $entityId->getSerialization() ];
	}
}
