<?php

namespace Wikibase\Repo\Specials;

use DataTypes\DataTypeFactory;
use Html;
use OutOfBoundsException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataTypeSelector;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\PropertyInfoStore;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * Special page to list properties by data type
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Adam Shorland
 */
class SpecialListProperties extends SpecialWikibaseQueryPage {

	/**
	 * Max server side caching time in seconds.
	 *
	 * @type integer
	 */
	const CACHE_TTL_IN_SECONDS = 30;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyInfoStore
	 */
	private $propertyInfoStore;

	/**
	 * @var LanguageFallbackLabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var string
	 */
	private $dataType;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var BufferingTermLookup
	 */
	private $bufferingTermLookup;

	public function __construct() {
		parent::__construct( 'ListProperties' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$wikibaseRepo->getDataTypeFactory(),
			$wikibaseRepo->getStore()->getPropertyInfoStore(),
			$wikibaseRepo->getEntityIdHtmlLinkFormatterFactory(),
			$wikibaseRepo->getLanguageFallbackChainFactory(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getBufferingTermLookup()
		);
	}

	/**
	 * Set service objects to use. Unit tests may call this to substitute mock
	 * services.
	 */
	public function initServices(
		DataTypeFactory $dataTypeFactory,
		PropertyInfoStore $propertyInfoStore,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		EntityTitleLookup $titleLookup,
		BufferingTermLookup $bufferingTermLookup
	) {
		$this->labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$bufferingTermLookup,
			$languageFallbackChainFactory->newFromLanguage( $this->getLanguage() )
		);

		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyInfoStore = $propertyInfoStore;
		$this->entityIdFormatter = $entityIdFormatterFactory->getEntityIdFormater(
			$this->labelDescriptionLookup
		);
		$this->titleLookup = $titleLookup;
		$this->bufferingTermLookup = $bufferingTermLookup;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.5
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$output = $this->getOutput();
		$output->setSquidMaxage( static::CACHE_TTL_IN_SECONDS );

		$this->prepareArguments( $subPage );
		$this->showForm();

		if ( $this->dataType !== null ) {
			$this->showQuery();
		}
	}

	private function prepareArguments( $subPage ) {
		$request = $this->getRequest();

		$this->dataType = $request->getText( 'datatype', $subPage );
		if ( $this->dataType !== '' && !in_array( $this->dataType, $this->dataTypeFactory->getTypeIds() ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-listproperties-invalid-datatype', $this->dataType )->escaped() );
			$this->dataType = null;
		}
	}

	private function showForm() {
		$dataTypeSelect = new DataTypeSelector(
			$this->dataTypeFactory->getTypes(),
			$this->getLanguage()->getCode()
		);

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'action' => $this->getPageTitle()->getLocalURL(),
					'name' => 'listproperties',
					'id' => 'wb-listproperties-form'
				)
			) .
			Html::openElement( 'fieldset' ) .
			Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-listproperties-legend' )->text()
			) .
			Html::openElement( 'p' ) .
			Html::element(
				'label',
				array(
					'for' => 'wb-listproperties-datatype'
				),
				$this->msg( 'wikibase-listproperties-datatype' )->text()
			) . ' ' .
			Html::rawElement(
				'select',
				array(
					'name' => 'datatype',
					'id' => 'wb-listproperties-datatype',
					'class' => 'wb-select'
				),
				Html::element(
					'option',
					array(
						'value' => '',
						'selected' => $this->dataType === ''
					),
					$this->msg( 'wikibase-listproperties-all' )->text()
				) .
				$dataTypeSelect->getOptionsHtml( $this->dataType )
			) . ' ' .
			Html::input(
				'',
				$this->msg( 'wikibase-listproperties-submit' )->text(),
				'submit',
				array(
					'id' => 'wikibase-listproperties-submit',
					'class' => 'wb-input-button'
				)
			) .
			Html::closeElement( 'p' ) .
			Html::closeElement( 'fieldset' ) .
			Html::closeElement( 'form' )
		);
	}

	/**
	 * Formats a row for display.
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 */
	protected function formatRow( $propertyId ) {
		$title = $this->titleLookup->getTitleForId( $propertyId );
		if ( !$title->exists() ) {
			return $this->entityIdFormatter->formatEntityId( $propertyId );
		}

		$row = $this->getIdHtml( $propertyId, $title );
		try {
			$label = $this->labelDescriptionLookup->getLabel( $propertyId )->getText();
			$row .= wfMessage( 'colon-separator' )->escaped() . $label;
		} catch ( OutOfBoundsException $e ) {
			// If there is no label do not add it
		}

		return $row;
	}

	/**
	 * Returns HTML representing the label in the display language (or an appropriate fallback).
	 *
	 * @param EntityId|null $entityId
	 * @param Title|null $title
	 *
	 * @return string HTML
	 */
	private function getIdHtml( EntityId $entityId = null, $title ) {
		$idElement =  Html::element(
			'a',
			array(
				'title' => $title ? $title->getPrefixedText() : $entityId->getSerialization(),
				'href' => $title ? $title->getLocalURL() : '',
				'class' => 'wb-itemlink-id'
			),
			$entityId->getSerialization()
		);

		return $idElement;
	}

	/**
	 * @param integer $offset Start to include at number of entries from the start title
	 * @param integer $limit Stop at number of entries after start of inclusion
	 *
	 * @return PropertyId[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$propertyInfo = array_slice( $this->getPropertyInfo(), $offset, $limit, true );

		$propertyIds = array();

		foreach ( $propertyInfo as $numericId => $info ) {
			$propertyIds[] = PropertyId::newFromNumber( $numericId );
		}

		$this->bufferingTermLookup->prefetchTerms( $propertyIds );

		return $propertyIds;
	}

	/**
	 * @return array[] An associative array mapping property IDs to info arrays.
	 */
	private function getPropertyInfo() {
		if ( $this->dataType === '' ) {
			$propertyInfo = $this->propertyInfoStore->getAllPropertyInfo();
		} else {
			$propertyInfo = $this->propertyInfoStore->getPropertyInfoForDataType(
				$this->dataType
			);
		}

		// NOTE: $propertyInfo uses numerical property IDs as keys!
		ksort( $propertyInfo );
		return $propertyInfo;
	}

}
