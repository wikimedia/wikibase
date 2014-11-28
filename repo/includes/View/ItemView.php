<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class for creating views for Item instances.
 * For the Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 */
class ItemView extends EntityView {

	/**
	 * @see EntityView::getMainHtml
	 */
	protected function getMainHtml( EntityRevision $entityRevision ) {
		$item = $entityRevision->getEntity();

		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( '$entityRevision must contain an Item.' );
		}

		$html = parent::getMainHtml( $entityRevision );
		$html .= $this->claimsView->getHtml(
			$item->getStatements()->toArray(),
			'wikibase-statements'
		);

		return $html;
	}

	/**
	 * @see EntityView::getSideHtml
	 */
	protected function getSideHtml( EntityRevision $entityRevision ) {
		$item = $entityRevision->getEntity();
		return $this->getHtmlForSiteLinks( $item );
	}

	/**
	 * @see EntityView::getTocSections
	 */
	protected function getTocSections() {
		$array = parent::getTocSections();
		$array['claims'] = 'wikibase-statements';
		$groups = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'siteLinkGroups' );
		foreach ( $groups as $group ) {
			$id = htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES );
			$array[$id] = 'wikibase-sitelinks-' . $group;
		}
		return $array;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.1
	 *
	 * @param Item $item the entity to render
	 *
	 * @return string
	 */
	protected function getHtmlForSiteLinks( Item $item ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$groups = $wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' );

		// FIXME: Inject this
		$siteLinksView = new SiteLinksView(
			$wikibaseRepo->getSiteStore()->getSites(),
			new SectionEditLinkGenerator(),
			$wikibaseRepo->getEntityLookup(),
			$this->language->getCode()
		);

		$itemId = $item->getId();

		return $siteLinksView->getHtml( $item->getSiteLinks(), $itemId, $groups, $this->editable );
	}

}
