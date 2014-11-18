<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelLookup;
use Wikibase\Lib\Store\LanguageLabelLookup;

/**
 * Factory for LabelLookup objects based on FormatterOptions.
 *
 * The LabelLookup is created based on the values of the options
 * 'LabelLookup', 'languages', and ValueFormatter::OPT_LANG:
 *
 * * 'LabelLookup' can be used to provide a custom LabelLookup instance directly
 * * If 'languages' is set, a LanguageFallbackLabelLookup will be created byed on
 *   the LanguageFallbackChain contained in that option.
 * * If ValueFormatter::OPT_LANG is set, a LanguageLabelLookup is created
 * * If none of these options is set, an InvalidArgumentException is thrown.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class FormatterLabelLookupFactory {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	public function __construct( TermLookup $termLookup ) {
		$this->termLookup = $termLookup;
	}

	/**
	 * @param FormatterOptions $options
	 *
	 * @throws InvalidArgumentException
	 * @return LabelLookup
	 */
	public function getLabelLookup( FormatterOptions $options ) {
		if ( $options->hasOption( 'LabelLookup' ) ) {
			$labelLookup = $options->getOption( 'LabelLookup' );

			if ( !( $labelLookup instanceof LabelLookup ) ) {
				throw new InvalidArgumentException( 'Option LabelLookup must be used ' .
					'with an instance of LabelLookup.' );
			}
		} elseif ( $options->hasOption( 'languages' ) ) {
			$fallbackChain = $options->getOption( 'languages' );

			if ( !( $fallbackChain instanceof LanguageFallbackChain ) ) {
				throw new InvalidArgumentException( 'Option `languages` must be used ' .
					'with an instance of LanguageFallbackChain.' );
			}

			$labelLookup = new LanguageFallbackLabelLookup(
				$this->termLookup,
				$fallbackChain
			);
		} else if ( $options->hasOption( ValueFormatter::OPT_LANG ) ) {
			$labelLookup = new LanguageLabelLookup(
				$this->termLookup,
				$options->getOption( ValueFormatter::OPT_LANG )
			);
		} else {
			throw new InvalidArgumentException( 'OPT_LANG or languages (fallback chain) '
				. 'must be set in FormatterOptions.' );
		}

		return $labelLookup;
	}

}
