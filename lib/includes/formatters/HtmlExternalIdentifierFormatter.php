<?php

namespace Wikibase\Lib\Formatters;

use DataValues\UnDeserializableValue;
use Html;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\SnakUrlExpander;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterTypeException;

/**
 * A formatter for PropertyValueSnaks that contain a StringValue that is interpreted
 * as an external identifier. The identifier is rendered as an HTML link to some resource about
 * the identifier. The link is created based on a URL pattern associated with the snak's property id
 * via a SnakUrlExpander.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class HtmlExternalIdentifierFormatter implements SnakFormatter {

	/**
	 * @var SnakUrlExpander
	 */
	private $urlExpander;

	/**
	 * @param SnakUrlExpander $urlExpander
	 */
	public function __construct( SnakUrlExpander $urlExpander ) {
		$this->urlExpander = $urlExpander;
	}

	/**
	 * Formats the given Snak's value as a HTML link.
	 * The URL of that link is determined using a SnakUrlExpander.
	 * If the snak could not be expanded into a URL, the identifier is returned as simple text.
	 *
	 * @param Snak $snak
	 *
	 * @throws ParameterTypeException if $snak is not a PropertyValueSnak, or if $snak->getDataValue()
	 * does not return a StringValue.
	 * @return string HTML
	 */
	public function formatSnak( Snak $snak ) {
		Assert::parameterType( 'Wikibase\DataModel\Snak\PropertyValueSnak', $snak, '$snak' );
		/** @var PropertyValueSnak $snak */

		$id = $snak->getDataValue()->getValue();
		$url = $this->urlExpander->expandUrl( $snak );
		$attr = array( 'class' => 'wb-external-id' );

		if ( $url === null ) {
			return Html::element( 'span', $attr, $id );
		} else {
			$attr['href'] = $url;
			return Html::element( 'a', $attr, $id );
		}
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string SnakFormatter::FORMAT_HTML
	 */
	public function getFormat() {
		return SnakFormatter::FORMAT_HTML;
	}

}
