<?php

namespace Wikibase\Client\Hooks;

use Language;
use OutputPage;
use Sanitizer;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Provides access to the badges of the current page's sitelinks
 * and adds some properties to the HTML output to display them.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageLinkBadgeDisplay {

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var array
	 */
	protected $badgeClassNames;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @param EntityLookup $entityLookup
	 * @param array $badgeClassNames
	 * @param Language $language
	 */
	public function __construct( EntityLookup $entityLookup, array $badgeClassNames, Language $language ) {
		$this->entityLookup = $entityLookup;
		$this->badgeClassNames = $badgeClassNames;
		$this->language = $language;
	}

	/**
	 * Attaches info about link badges in the given OutputPage, for later retrieval
	 * and processing by applyBadges().
	 *
	 * @param SiteLink[] $langLinks A list of language links as
	 * @param OutputPage $output The output page to set the wikibase_badges property on.
	 */
	public function attachBadgesToOutput( array $langLinks, OutputPage $output ) {
		$badgeInfoForAllLinks = array();

		foreach ( $langLinks as $key => $link ) {
			$badges = $link->getBadges();

			if ( !empty( $badges ) ) {
				$badgeInfoForAllLinks[$key] = $this->getBadgeInfo( $badges );
			}
		}

		$output->setProperty( 'wikibase_badges', $badgeInfoForAllLinks );
	}

	/**
	 * Applies the badges described in the wikibase_badges property of $output to
	 * the language link to $languageLinkTitle. The badge info for this linked is
	 * looked up in the wikibase_badges data using the key returned by
	 * $languageLinkTitle->getInterwiki().
	 *
	 * @since 0.5
	 *
	 * @param Title $languageLinkTitle
	 * @param array &$languageLink
	 * @param OutputPage $output The output page to take the wikibase_badges property from.
	 */
	public function applyBadges( Title $languageLinkTitle, array &$languageLink, OutputPage $output ) {
		$badges = $output->getProperty( 'wikibase_badges' );

		if ( empty( $badges ) ) {
			return;
		}

		$navId = $languageLinkTitle->getInterwiki();
		if ( !isset( $badges[$navId] ) ) {
			return;
		}

		/** @var array $linkBade an associative array with the keys 'badges', 'class', and 'itemtitle'. */
		$linksBadgeInfo = $badges[$navId];

		if ( isset( $languageLink['class'] ) ) {
			$languageLink['class'] .= ' ' . $linksBadgeInfo['class'];
		} else {
			$languageLink['class'] = $linksBadgeInfo['class'];
		}

		$languageLink['itemtitle'] = $linksBadgeInfo['itemtitle'];
	}

	/**
	 * Builds badge information for the given badges.
	 * CSS classes are derived from the given list of badges, and any extra badge class
	 * names specified in the badgeClassNames setting are added.
	 * For badges that have a such an extra class name assigned, this also
	 * adds a title according to the items' labels. Other badges do not have labels
	 * added to the link's title attribute, so the can be effectively ignored
	 * on this client wiki.
	 *
	 * @param ItemId[] $badges
	 *
	 * @return array An associative array with the keys 'class' and 'itemtitle' with assigned
	 * string values. These fields correspond to the fields in the description array for language
	 * links used by the SkinTemplateGetLanguageLink hook and expected by the applyBadges()
	 * function.
	 */
	private function getBadgeInfo( array $badges ) {
		$classes = array();
		$titles = array();

		foreach ( $badges as $badge ) {
			$badgeSerialization = $badge->getSerialization();
			$classes[] = 'badge-' . Sanitizer::escapeClass( $badgeSerialization );

			// nicer classes for well known badges
			if ( isset( $this->badgeClassNames[$badgeSerialization] ) ) {
				// add class name
				$className = Sanitizer::escapeClass( $this->badgeClassNames[$badgeSerialization] );
				$classes[] = $className;

				// add title (but only if this badge is well known on this wiki)
				$title = $this->getLabel( $badge );

				if ( $title !== null ) {
					$titles[] = $title;
				}
			}
		}

		$info = array(
			'class' => implode( ' ', $classes ),
			'itemtitle' => $this->language->commaList( $titles ),
		);

		return $info;
	}

	/**
	 * Returns the label for the given badge.
	 *
	 * @since 0.5
	 *
	 * @param ItemId $badge
	 *
	 * @return string|null
	 */
	private function getLabel( ItemId $badge ) {
		$entity = $this->entityLookup->getEntity( $badge );
		if ( !$entity ) {
			return null;
		}

		$title = $entity->getLabel( $this->language->getCode() );
		if ( !$title ) {
			return null;
		}
		return $title;
	}

}
