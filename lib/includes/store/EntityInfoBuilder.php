<?php

namespace Wikibase\Lib\Store;
use Wikibase\DataModel\Entity\EntityId;

/**
 * A builder for collecting information about a batch of entities in an efficient way.
 *
 * @note: The batch of entities to work on would typically be supplied to the constructor
 * by the newEntityInfoBuilder method of the EntityBuilderFactory.
 *
 * @see EntityBuilderFactory
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityInfoBuilder {

	/**
	 * Returns an entity info data structure for the entities this builder is defined to
	 * operate on (typically, based on a list of EntityIds supplied to the constructor
	 * by an implementation of EntityBuilderFactory::newEntityInfoBuilder).
	 *
	 * The entity info is represented by a nested array structure. On the top level,
	 * entity id strings are used as keys that refer to entity "records".
	 *
	 * Each record is an associative array with at least the fields "id" and "type".
	 * Which other fields are present depends on which methods have been called on
	 * the EntityInfoBuilder in order to gather information about the entities.
	 *
	 * The array structure should be compatible with the structure generated by
	 * EntitySerializer and related classes. It should be suitable for serialization,
	 * and must thus not contain any objects.
	 *
	 * @note: after resolveRedirects() is called, entity records will be available under
	 * their actual ID as well as any relevant redirect ID. If records should only be
	 * available under the ID supplied to the builder's constructor, use retain() to
	 * strip any others.
	 *
	 * @return array[]
	 */
	public function getEntityInfo();

	/**
	 * Resolves any redirects.
	 *
	 * This updates the 'id' field of the records in the data structure
	 * returned by getEntityInfo() to the id of the target redirect, if the
	 * original ID referred to a redirect.
	 *
	 * Thus, the keys in the data structure returned by getEntityInfo()
	 * may come to be different from the respective record's id field.
	 */
	public function resolveRedirects();

	/**
	 * Adds terms (like labels and/or descriptions) to the entity info.
	 * After calling this, the entity records in the data structure returned by getEntityInfo
	 * may have entries for the given term types (e.g. 'labels', 'descriptions', or 'aliases').
	 *
	 * @note: For historical reasons, the types expected by $termTypes are different from the
	 * keys used in entity info!
	 *
	 * @note: If resolveRedirects() was previously called, terms from any redirect's target
	 * entity are used.
	 *
	 * @param string[]|null $termTypes Which types of terms to include (e.g. "label", "description",
	 * "alias"). Note that the corresponding fields that will be set set in the entity records
	 * use plural names ("labels", "descriptions", and "aliases" respectively).
	 * @param string[]|null $languages Which languages to include
	 */
	public function collectTerms( array $termTypes = null, array $languages = null );

	/**
	 * Adds property data types to the entries in $entityInfo. Entities that do not have a data type
	 * remain unchanged.
	 *
	 * After calling this, the entity records in the data structure returned by getEntityInfo
	 * will have a 'datatype' field if they represent a Property entity.
	 *
	 */
	public function collectDataTypes();

	/**
	 * Removes entries for non-existent Entities from this builder. The data structure
	 * returned by getEntityInfo will then only contain records for entities that could
	 * actually be found.
	 *
	 * @note: If resolveRedirects() was previously called, any redirects are kept.
	 * If resolveRedirects() was not called, redirects will be removed as missing.
	 */
	public function removeMissing();

	/**
	 * Remove info records for the given EntityIds.
	 *
	 * @param EntityId[] $ids
	 */
	public function remove( $ids );

	/**
	 * Retain only info records for the given EntityIds.
	 * Useful e.g. after resolveRedirects(), to remove explicit entries for
	 * redirect targets not present in the original input.
	 *
	 * @param EntityId[] $ids
	 */
	public function retain( $ids );
}
