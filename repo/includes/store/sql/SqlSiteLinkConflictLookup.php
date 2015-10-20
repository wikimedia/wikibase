<?php

namespace Wikibase\Repo\Store\Sql;

use DatabaseBase;
use DBAccessBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Repo\Store\SiteLinkConflictLookup;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlSiteLinkConflictLookup extends DBAccessBase implements SiteLinkConflictLookup,
	EntityStoreWatcher {

	/**
	 * @var ItemId[]
	 */
	private $itemConflictsToIgnore = array();

	/**
	 * @see SiteLinkConflictLookup::getConflictsForItem
	 *
	 * @param Item $item
	 * @param DatabaseBase|null $db
	 *
	 * @return array[]
	 */
	public function getConflictsForItem( Item $item, DatabaseBase $db = null ) {
		$siteLinks = $item->getSiteLinkList();

		if ( $siteLinks->isEmpty() ) {
			return array();
		}

		if ( $db ) {
			$dbr = $db;
		} else {
			$dbr = $this->getConnection( DB_SLAVE );
		}

		$anyOfTheLinks = '';

		/** @var SiteLink $siteLink */
		foreach ( $siteLinks as $siteLink ) {
			if ( $anyOfTheLinks !== '' ) {
				$anyOfTheLinks .= "\nOR ";
			}

			$anyOfTheLinks .= '(';
			$anyOfTheLinks .= 'ips_site_id=' . $dbr->addQuotes( $siteLink->getSiteId() );
			$anyOfTheLinks .= ' AND ';
			$anyOfTheLinks .= 'ips_site_page=' . $dbr->addQuotes( $siteLink->getPageName() );
			$anyOfTheLinks .= ')';
		}

		// TODO: $anyOfTheLinks might get very large and hit some size limit imposed
		//       by the database. We could chop it up of we know that size limit.
		//       For MySQL, it's select @@max_allowed_packet.

		$conflictingLinks = $dbr->select(
			'wb_items_per_site',
			array(
				'ips_site_id',
				'ips_site_page',
				'ips_item_id',
			),
			"($anyOfTheLinks) AND ips_item_id != " . (int)$item->getId()->getNumericId(),
			__METHOD__
		);

		$conflicts = array();

		foreach ( $conflictingLinks as $link ) {
			$numericId = (int)$link->ips_item_id;

			if ( !array_key_exists( $numericId, $this->itemConflictsToIgnore ) ) {
				$conflicts[] = array(
					'siteId' => $link->ips_site_id,
					'itemId' => $numericId,
					'sitePage' => $link->ips_site_page,
				);
			}
		}

		if ( !$db ) {
			$this->releaseConnection( $dbr );
		}

		return $conflicts;
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		// no-op
	}

	/**
	 * @see EntityStoreWatcher::entityUpdated
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$entity = $entityRevision->getEntity();

		if ( $entity instanceof Item ) {
			$itemId = $entity->getId();

			if ( $itemId instanceof ItemId ) {
				$numericId = $itemId->getNumericId();
				$this->itemConflictsToIgnore[$numericId] = $itemId;
			}
		}
	}

	/**
	 * @see EntityStoreWatcher::redirectUpdated
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		// no-op
	}

}
