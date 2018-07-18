<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;

/**
 * @license GPL-2.0-or-later
 */
class EntityRetrievingLabelDescriptionLookupForBatch implements LabelDescriptionLookupForBatch {

	/**
	 * @var EntityRetrievingTermLookup
	 */
	private $termLookup;

	public function __construct( EntityRetrievingTermLookup $termLookup ) {
		$this->termLookup = $termLookup;
	}

	public function getLabels( array $ids, array $languageCodes ) {
		$labels = [];

		foreach ( $ids as $id ) {
			try {
				$labels[$id->getSerialization()] = $this->termLookup->getLabels( $id, $languageCodes );
			} catch ( TermLookupException $e ) {
				// No such entity, do not include in results (TODO: this is evil)
			}
		}

		return $labels;
	}

	public function getDescriptions( array $ids, array $languageCodes ) {
		$descriptions = [];

		foreach ( $ids as $id ) {
			try {
				$descriptions[$id->getSerialization()] = $this->termLookup->getDescriptions( $id, $languageCodes );
			} catch ( TermLookupException $e ) {
				// No such entity, do not include in results (TODO: this is evil)
			}
		}

		return $descriptions;
	}

}
