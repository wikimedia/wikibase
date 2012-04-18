<?php

/**
 * API module to get the link sites for a single Wikibase item.
 *
 * @since 0.1
 *
 * @file ApiWikibaseGetItem.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseGetItem extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title '] ) ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}

		$success = false;

		if ( !isset( $params['id'] ) ) {
			$params['id'] = WikibaseItem::getIdForSiteLink( $params['site'], $params['title'] );

			if ( $params['id'] === false ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}

		$page = WikibaseItem::getWikiPageForId( $params['id'] );
		$content = $page->getContent();

		if ( $content->getModelName() !== CONTENT_MODEL_WIKIBASE ) {
			$this->dieUsage( wfMsg( 'wikibase-api-invalid-contentmodel' ), 'invalid-contentmodel' );
		}

		$item = $content->getItem();

		$sitelinks = $item->getSiteLinks();
		$this->getResult()->addValue(
			'page', 
			'sitelinks',
			(int)$success
		);

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
	}

	public function getAllowedParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
				/*ApiBase::PARAM_ISMULTI => true,*/
			),
			'site' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				/*ApiBase::PARAM_ISMULTI => true,*/
			),
		);
	}

	public function getParamDescription() {
		return array(
			'id' => 'The ID of the item to get the data from',
			'title' => array( 'The title of the corresponding page',
				"Use together with 'site'."
			),
			'site' => array( 'Identifier for the site on which the corresponding page resides',
				"Use together with 'title'."
			),
		);
	}

	public function getDescription() {
		return array(
			'API module to get the data for a single Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'id-xor-wikititle', 'info' => 'You need to either provide the item id or the title of a corresponding page and the identifier for the wiki this page is on' ),
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbgetitem&id=42'
			=> 'Get item number 42',
			'api.php?action=wbgetitem&site=en&title=Berlin'
			=> 'Get the item associated to page Berlin on the site identified by "en"',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wbgetlinksites';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
