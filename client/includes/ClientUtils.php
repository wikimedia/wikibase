<?php

namespace Wikibase;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
final class ClientUtils {

	/**
	 * @since 0.2
	 *
	 * @return string
	 */
	public static function baseUrl() {
		$baseUrl = Settings::get( 'repoUrl' );
		$baseUrl = rtrim( $baseUrl, '/' );
		return wfExpandUrl( $baseUrl, PROTO_RELATIVE );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $target
	 *
	 * @return string
	 */
	public static function repoArticleUrl( $target ) {
		return self::baseUrl() . str_replace( '$1', $target, Settings::get( 'repoArticlePath' ) );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $target
	 * @param string $text
	 * @param array $attribs
	 i*
	 * @return string
	 */
	public static function repoLink( $target, $text, $attribs = array() ) {
		$baseUrl = self::baseUrl();

		if ( array_key_exists( 'query', $attribs ) && is_array( $attribs['query'] ) ) {
			$repoScriptPath = Settings::get( 'repoScriptPath' );
			if ( $attribs['query']['type'] === 'index' ) {
				$url = $baseUrl . $repoScriptPath . '/index.php';
			} else if ( $attribs['query']['type'] === 'api' ) {
				$url = $baseUrl . $repoScriptPath . '/api.php';
			} else {
				throw new \MWException( 'Invalid query type' );
			}
			$url = wfAppendQuery( $url, wfArrayToCgi( $attribs['query']['params'] ) );
			unset( $attribs['query'] );
		} else {
			$url = self::repoArticleUrl( $target );
		}

		if ( $url === null ) {
			throw new \MWException( 'Could not build a repoLink url.' );
		}

		$class = 'plainlinks';
		if ( array_key_exists( 'class', $attribs ) ) {
			$class .= ' ' . $attribs['class'];
		}

		$attribs['class'] = $class;
		$attribs['href'] = $url;

		return \Html::element( 'a', $attribs, $text );
	}

	/**
	 * Checks whether to include Wikibase Client module and try to display them
	 * based on namespace and presence of languages links in OutputPage.  Depending on action,
	 * Wikibase and other interwiki links may or may not be included in OutputPage.
	 *
	 * @since 0.4
	 *
	 * @param \IContextSource $context
	 *
	 * @return bool
	 */
	public static function includeWikibaseLinks( \IContextSource $context ) {
		$links = $context->getOutput()->getLanguageLinks();
		$title = $context->getTitle();

		if ( in_array( $title->getNamespace(), Settings::get( 'namespaces' ) ) && !empty( $links ) ) {
			return true;
		}

		return false;
	}

}
