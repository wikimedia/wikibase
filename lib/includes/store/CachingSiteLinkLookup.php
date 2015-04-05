<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * SiteLinkLookup implementation that caches the obtained data (except for data obtained
 * via "getLinks").
 * Note: This doesn't implement any means of purging or data invalidation beyond the cache
 * timeout.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class CachingSiteLinkLookup implements SiteLinkLookup {

	/**
	 * @var SiteLinkLookup
	 */
	private $lookup;

	/**
	 * The cache to use for caching entities.
	 *
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @param SiteLinkLookup $siteLinkLookup The lookup to use
	 * @param BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds. Defaults to 3600 (1 hour).
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		BagOStuff $cache,
		$cacheDuration = 3600
	) {
		$this->lookup = $siteLinkLookup;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;
	}

	/**
	 * @see SiteLinkLookup::getItemIdForLink
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return ItemId|null
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle ) {
		$itemIdSerialization = $this->cache->get( $this->getByPageCacheKey( $globalSiteId, $pageTitle ) );

		if ( is_string( $itemIdSerialization ) ) {
			return new ItemId( $itemIdSerialization );
		} elseif ( $itemIdSerialization === false ) {
			return $this->getAndCacheItemIdForLink( $globalSiteId, $pageTitle );
		}

		return null;
	}

	/**
	 * @see SiteLinkLookup::getLinks
	 * This is uncached!
	 *
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return array[]
	 */
	public function getLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() ) {
		// Caching this would be rather complicated for little to no benefit.
		return $this->lookup->getLinks( $numericIds, $siteIds, $pageNames );
	}

	/**
	 * Returns an array of SiteLink objects for an item. If the item isn't known or not an Item,
	 * an empty array is returned.
	 *
	 * @since 0.4
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinksForItem( ItemId $itemId ) {
		$cacheKey = 'wikibase-sitelinks:' . $itemId->getSerialization();
		$siteLinks = $this->cache->get( $cacheKey );

		if ( !is_array( $siteLinks ) ) {
			$siteLinks = $this->lookup->getSiteLinksForItem( $itemId );
			$this->cache->set( $cacheKey, $siteLinks, $this->cacheDuration );
		}

		return $siteLinks;
	}

	/**
	 * @see SiteLinkLookup::getEntityIdForSiteLink
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getEntityIdForSiteLink( SiteLink $siteLink ) {
		return $this->getItemIdForLink(
			$siteLink->getSiteId(),
			$siteLink->getPageName()
		);
	}

	/**
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return string
	 */
	private function getByPageCacheKey( $globalSiteId, $pageTitle ) {
		return 'wikibase-sitelinks-by-page:' . $globalSiteId . ':' . $pageTitle;
	}

	/**
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return ItemId|null
	 */
	private function getAndCacheItemIdForLink( $globalSiteId, $pageTitle ) {
		$itemId = $this->lookup->getItemIdForLink( $globalSiteId, $pageTitle );

		if ( $itemId instanceof ItemId ) {
			$this->cache->set(
				$this->getByPageCacheKey( $globalSiteId, $pageTitle ),
				$itemId->getSerialization(),
				$this->cacheDuration
			);
		}

		return $itemId;
	}
}
