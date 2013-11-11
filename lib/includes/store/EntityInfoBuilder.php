<?php

namespace Wikibase;

/**
 * A service for collecting basic information about a build of entities.
 *
 * Information about each entity is represented by nested arrays in the same form
 * as generated by EntitySerializer and related classes.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityInfoBuilder {

	/**
	 * Builds basic stubs of entity info records based on the given list of entity IDs.
	 *
	 * @param EntityId[] $ids
	 *
	 * @return array A map of prefixed entity IDs to records representing an entity each.
	 */
	public function buildEntityInfo( array $ids );

	/**
	 * Adds terms (like labels and/or descriptions) to
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 * @param array $types Which types of terms to include (e.g. "label", "description", "aliases").
	 * @param array $languages Which languages to include
	 */
	public function addTerms( array &$entityInfo, array $types = null, array $languages = null );

	/**
	 * Adds property data types to the entries in $entityInfo. Entities that do not have a data type
	 * remain unchanged.
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 */
	public function addDataTypes( array &$entityInfo );
}
