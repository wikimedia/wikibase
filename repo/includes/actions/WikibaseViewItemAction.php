<?php

/**
 * Handles the view action for Wikibase items.
 *
 * TODO: utilized CachedAction once in core
 *
 * @since 0.1
 *
 * @file WikibaseViewItemAction.php
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseViewItemAction extends FormlessAction {

	/**
	 * (non-PHPdoc)
	 * @see Action::getName()
	 */
	public function getName() {
		return 'view';
	}

	/**
	 * (non-PHPdoc)
	 * @see FormlessAction::onView()
	 */
	public function onView() {
		$content = $this->getContext()->getWikiPage()->getContent();
		$contentLangCode = $this->getLanguage()->getCode();

		$parserOutput = $content->getParserOutput( $this->getContext() );

		$out = $this->getOutput();

		$out->addHTML( $parserOutput->getText() );

		// make sure required client sided resources will be loaded:
		$out->addModules( 'wikibase' );

		// overwrite page title
		$out->setPageTitle( $content->getLabel( $contentLangCode ) );

		// hand over the itemId to JS
		$out->addJsConfigVars( 'wbItemId', $content->getId() );
		// TODO: make this configurable rather than using the language array here:
		$out->addJsConfigVars( 'wbSiteLinks', Language::fetchLanguageName( $contentLangCode ) );

		return '';
	}

	/**
	 * (non-PHPdoc)
	 * @see Action::getDescription()
	 */
	protected function getDescription() {
		return '';
	}

}