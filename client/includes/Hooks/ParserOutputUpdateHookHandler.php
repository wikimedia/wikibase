<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use Content;
use MediaWiki\Content\Hook\ContentAlterParserOutputHook;
use ParserOutput;
use Title;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @license GPL-2.0-or-later
 */
class ParserOutputUpdateHookHandler implements ContentAlterParserOutputHook {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var LangLinkHandlerFactory
	 */
	private $langLinkHandlerFactory;

	/**
	 * @var ClientParserOutputDataUpdater
	 */
	private $parserOutputDataUpdater;

	/**
	 * @var EntityUsageFactory
	 */
	private $entityUsageFactory;

	public function __construct(
		NamespaceChecker $namespaceChecker,
		LangLinkHandlerFactory $langLinkHandlerFactory,
		ClientParserOutputDataUpdater $parserOutputDataUpdater,
		EntityUsageFactory $entityUsageFactory
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->langLinkHandlerFactory = $langLinkHandlerFactory;
		$this->parserOutputDataUpdater = $parserOutputDataUpdater;
		$this->entityUsageFactory = $entityUsageFactory;
	}

	public static function factory(
		EntityIdParser $entityIdParser,
		LangLinkHandlerFactory $langLinkHandlerFactory,
		NamespaceChecker $namespaceChecker,
		ClientParserOutputDataUpdater $parserOutputDataUpdater
	): self {
		return new self(
			$namespaceChecker,
			$langLinkHandlerFactory,
			$parserOutputDataUpdater,
			new EntityUsageFactory( $entityIdParser )
		);
	}

	/**
	 * Handler for the ContentAlterParserOutput hook, which runs after internal parsing.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ContentAlterParserOutput
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public function onContentAlterParserOutput( $content, $title, $parserOutput ): void {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$this->doContentAlterParserOutput( $title, $parserOutput );
	}

	/**
	 * @internal only public for testing (to bypass the test skip in onContentAlterParserOutput)
	 */
	public function doContentAlterParserOutput( Title $title, ParserOutput $parserOutput ): void {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return;
		}

		$usageAccumulator = new ParserOutputUsageAccumulator( $parserOutput, $this->entityUsageFactory );
		$langLinkHandler = $this->langLinkHandlerFactory->getLangLinkHandler( $usageAccumulator );
		$useRepoLinks = $langLinkHandler->useRepoLinks( $title, $parserOutput );

		if ( $useRepoLinks ) {
			// add links
			$langLinkHandler->addLinksFromRepository( $title, $parserOutput );
		}

		$this->parserOutputDataUpdater->updateItemIdProperty( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateTrackingCategories( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateOtherProjectsLinksData( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutput );
	}

}
