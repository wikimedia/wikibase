<?php

namespace Wikibase\Client\Hooks;

use Parser;
use Wikibase\Client\Store\DescriptionLookup;

/**
 * Handles the {{SHORTDESC:...}} parser function.
 *
 * @license GPL-2.0+
 */
class ShortDescHandler {

	/**
	 * Parser function callback
	 *
	 * @param Parser $parser
	 * @param string $shortDesc Short description of the current page, as plain text.
	 *
	 * @return string
	 */
	public static function handle( Parser $parser, $shortDesc ) {
		$handler = self::newFromGlobalState();
		$handler->doHandle( $parser, $shortDesc );
		return '';
	}

	/**
	 * @return self
	 */
	private static function newFromGlobalState() {
		return new self();
	}

	/**
	 * Validates a short description.
	 * Valid descriptions are not empty (contain something other than whitespace/punctuation).
	 *
	 * @param string $shortDesc Short description of the current page, as plain text.
	 *
	 * @return bool
	 */
	public function isValid( $shortDesc ) {
		return !preg_match( '/^[\s\p{P}\p{Z}]*$/u', $shortDesc );
	}

	/**
	 * Sanitizes a short description by converting it into plaintext.
	 *
	 * Note that the sanitized description can still contain HTML (that was encoded as entities in
	 * the original) as there is no reason why someone shouldn't mention HTML tags in a description.
	 * No effort is made to handle trickier cases like <pre> correctly as there is no legitimate
	 * reason to use anything like that in {{SHORTDESC:...}}.
	 *
	 * @param string $shortDesc Short description of the current page, as HTML.
	 *
	 * @return string Plaintext of description.
	 */
	public function sanitize( $shortDesc ) {
		return trim( html_entity_decode( strip_tags( $shortDesc ), ENT_QUOTES, 'utf-8' ) );
	}

	/**
	 * Parser function
	 *
	 * @param Parser $parser
	 * @param string $shortDesc Short description of the current page, as plain text.
	 *
	 * @return void
	 */
	public function doHandle( Parser $parser, $shortDesc ) {
		$shortDesc = $this->sanitize( $shortDesc );
		if ( $this->isValid( $shortDesc ) ) {
			$out = $parser->getOutput();
			$out->setProperty( DescriptionLookup::LOCAL_PROPERTY_NAME, $shortDesc );
		}
	}

}
