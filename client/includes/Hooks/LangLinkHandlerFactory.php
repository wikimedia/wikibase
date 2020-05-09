<?php

namespace Wikibase\Client\Hooks;

use Psr\Log\LoggerInterface;
use SiteLookup;
use Wikibase\Client\NamespaceChecker;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class LangLinkHandlerFactory {

	/**
	 * @var LanguageLinkBadgeDisplay
	 */
	private $badgeDisplay;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string
	 */
	private $siteGroup;

	/**
	 * @param LanguageLinkBadgeDisplay $badgeDisplay
	 * @param NamespaceChecker $namespaceChecker determines which namespaces wikibase is enabled on
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param SiteLookup $siteLookup
	 * @param LoggerInterface $logger
	 * @param string $siteId The global site ID for the local wiki
	 * @param string $siteGroup The ID of the site group to use for showing language links.
	 */
	public function __construct(
		LanguageLinkBadgeDisplay $badgeDisplay,
		NamespaceChecker $namespaceChecker,
		SiteLinkLookup $siteLinkLookup,
		EntityLookup $entityLookup,
		SiteLookup $siteLookup,
		LoggerInterface $logger,
		$siteId,
		$siteGroup
	) {
		$this->badgeDisplay = $badgeDisplay;
		$this->namespaceChecker = $namespaceChecker;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->siteLookup = $siteLookup;
		$this->logger = $logger;
		$this->siteId = $siteId;
		$this->siteGroup = $siteGroup;
	}

	public function getLangLinkHandler(): LangLinkHandler {
		return new LangLinkHandler(
			$this->badgeDisplay,
			$this->namespaceChecker,
			new SiteLinksForDisplayLookup(
				$this->siteLinkLookup,
				$this->entityLookup,
				$this->logger,
				$this->siteId
			),
			$this->siteLookup,
			$this->siteId,
			$this->siteGroup
		);
	}
}
