<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LanguageLabelLookup implements LabelLookup {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param TermLookup $termLookup
	 * @param string $languageCode
	 */
	public function __construct( TermLookup $termLookup, $languageCode ) {
		$this->termLookup = $termLookup;
		$this->languageCode = $languageCode;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException
	 * @return Term
	 */
	public function getLabel( EntityId $entityId ) {
		$text = $this->termLookup->getLabel( $entityId, $this->languageCode );
		return new Term( $this->languageCode, $text );
	}

}
