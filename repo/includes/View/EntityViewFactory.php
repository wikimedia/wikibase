<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\EntityView;
use Wikibase\ItemView;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\PropertyView;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityViewFactory {

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var OutputFormatIdFormatterFactory
	 */
	private $idFormatterFactory;

	public function __construct(
		OutputFormatIdFormatterFactory $idFormatterFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory
	) {
		$this->idFormatterFactory = $idFormatterFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
	}

	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @param string $entityType
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $fallbackChain
	 * @param LabelLookup|null $labelLookup
	 * @param bool $editable
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	public function newEntityView(
		$entityType,
		$languageCode,
		LanguageFallbackChain $fallbackChain = null,
		LabelLookup $labelLookup = null,
		$editable = true
	 ) {
		$fingerprintView = $this->newFingerprintView( $languageCode );
		$claimsView = $this->newClaimsView( $languageCode, $fallbackChain, $labelLookup );

		// @fixme all that seems needed in EntityView is language code and dir.
		$language = Language::factory( $languageCode );

		// @fixme support more entity types
		if ( $entityType === 'item' ) {
			return new ItemView( $fingerprintView, $claimsView, $language, $editable );
		} elseif ( $entityType === 'property' ) {
			return new PropertyView( $fingerprintView, $claimsView, $language, $editable );
		}

		throw new InvalidArgumentException( 'No EntityView for entity type: ' . $entityType );
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $fallbackChain
	 * @param LabelLookup|null $labelLookup
	 *
	 * @return ClaimsView
	 */
	private function newClaimsView(
		$languageCode,
		LanguageFallbackChain $fallbackChain = null,
		LabelLookup $labelLookup = null
	) {
		$propertyIdFormatter = $this->getPropertyIdFormatter( $languageCode, $fallbackChain, $labelLookup );

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->getSnakFormatter( $languageCode, $fallbackChain, $labelLookup ),
			$propertyIdFormatter
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator
		);

		return new ClaimsView(
			$propertyIdFormatter,
			$this->sectionEditLinkGenerator,
			$claimHtmlGenerator
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return FingerprintView
	 */
	private function newFingerprintView( $languageCode ) {
		return new FingerprintView(
			$this->sectionEditLinkGenerator,
			$languageCode
		);
	}

	/**
	 * @param $languageCode
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param LabelLookup $labelLookup
	 *
	 * @return FormatterOptions
	 */
	private function getFormatterOptions(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain = null,
		LabelLookup $labelLookup = null
	) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );

		if ( $languageFallbackChain ) {
			$formatterOptions->setOption( 'languages', $languageFallbackChain );
		}

		if ( $labelLookup ) {
			$formatterOptions->setOption( 'LabelLookup', $labelLookup );
		}

		return $formatterOptions;
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $languageFallbackChain
	 * @param LabelLookup|null $labelLookup
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain = null,
		LabelLookup $labelLookup = null
	) {
		$formatterOptions = $this->getFormatterOptions( $languageCode, $languageFallbackChain, $labelLookup );

		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$formatterOptions
		);
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $languageFallbackChain
	 * @param LabelLookup|null $labelLookup
	 *
	 * @return EntityIdFormatter
	 */
	private function getPropertyIdFormatter(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain = null,
		LabelLookup $labelLookup = null
	) {
		$formatterOptions = $this->getFormatterOptions( $languageCode, $languageFallbackChain, $labelLookup );

		return $this->idFormatterFactory->getEntityIdFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$formatterOptions
		);
	}

}
