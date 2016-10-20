<?php

namespace Wikibase\Client\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * TODO: Docs
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class EntityIdSiteLinkFormatter implements EntityIdFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 */
	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string Wikitext
	 */
	public function formatEntityId( EntityId $entityId ) {
		$term = null;

		try {
			$term = $this->labelDescriptionLookup->getLabel( $entityId );
		} catch ( LabelDescriptionLookupException $ex ) {
		}

		// TODO: Add language fallback indicator
		$label = $term ? wfEscapeWikiText( $term->getText() ) : '';

		if ( $entityId instanceof ItemId ) {
			$title = $this->entityTitleLookup->getTitleForId( $entityId );

			if ( $title !== null ) {
				$pageName = $title->getFullText();
				$optionalLabel = $label === '' ? '' : '|' . $label;

				return '[[' . $pageName . $optionalLabel . ']]';
			}
		}

		return $label;
	}

}
