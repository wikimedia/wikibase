<?php

namespace Wikibase;
use ApiBase, User, Http, Language;

/**
 * API module to associate a page on a site with a Wikibase item or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseLinkSite.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiSetSiteLink extends ApiModifyItem {

	/**
	 * @see  ApiModifyItem::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Item $item, array $params ) {
		$permissions = parent::getRequiredPermissions( $item, $params );

		$permissions[] = 'sitelink-' . ( strlen( $params['linktitle'] ) ? 'update' : 'remove' );
		return $permissions;
	}

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		if ( isset( $params['linktitle'] ) ) {
			$params['linktitle'] = isset( $params['linktitle'] ) ? Utils::squashToNFC( $params['linktitle'] ) : '';
		}
	}

	/**
	 * Make a string for an autocomment.
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @param $plural integer|string the number used for plural forms
	 * @return string that can be used as an autocomment
	 */
	protected function autoComment( array $params, $plural = 1 ) {
		if ( isset( $params['linktitle'] ) && $params['linktitle'] !== "" ) {
			$comment = "set-sitelink";
		}
		else {
			$comment = "remove-sitelink";
		}
		return $comment . SUMMARY_COLON . $params['linksite'] . SUMMARY_GROUPING . $plural;
	}

	/**
	 * Make a string for an autosummary.
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @return array with a count of items, a string that can be used as an autosummary and the language
	 */
	protected function autoSummary( array $params ) {
		global $wgContLang, $wgLang;
		$lang = $wgContLang;
		if ( isset( $params['linksite'] ) ) {
			$site = Sites::singleton()->getSiteByGlobalId( $params['linksite'] );
			$lang = Language::factory( $site->getLanguage() );
		}
		$summary = array();
		if ( isset( $params['linktitle'] ) && $params['linktitle'] !== "" ) {
			$summary = self::pickValuesFromParams( $params, 'linktitle' );
		}
		$list = $lang->commaList( $summary );
		if ($list !== "") {
			$list = $lang->getDirMark() 	// dirmark according to the language for the string(s)
				. $list						// merged list og string(s)
				. $wgLang->getDirMark();	// dirmark according to the user language
		}
		return array( count( $summary ), $list, $lang );
	}

	/**
	 * Create the item if its missing.
	 *
	 * @since    0.1
	 *
	 * @param array       $params
	 *
	 * @internal param \Wikibase\ItemContent $itemContent
	 * @return ItemContent Newly created item
	 */
	protected function createItem( array $params ) {
		$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
	}

	/**
	 * @see ApiModifyItem::modifyItem()
	 *
	 * @since 0.1
	 *
	 * @param ItemContent $itemContent
	 * @param array $params
	 *
	 * @return boolean Success indicator
	 */
	protected function modifyItem( ItemContent &$itemContent, array $params ) {

		if ( isset( $params['linksite'] ) && ( $params['linktitle'] === '' ) ) {
			$sitelinks = $itemContent->getItem()->getSiteLinks();
			if ( !isset( $sitelinks[$params['linksite']] ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-remove-sitelink-failed' ), 'remove-sitelink-failed' );
			}

			$this->addSiteLinksToResult( array( $params['linksite'] => $sitelinks[$params['linksite']] ), 'item' );
			$itemContent->getItem()->removeSiteLink( $params['linksite'] );
			return true;
		}
		else {
			// Clean up initial and trailing spaces and compress rest of the spaces.
			$linktitle = Utils::squashToNFC( $params['linktitle'] );
			if ( !isset( $linktitle ) || $linktitle === "" ) {
				$this->dieUsage( wfMsg( 'wikibase-api-empty-link-title' ), 'empty-link-title' );
			}

			$data = $this->queryPageAtSite( $params['linksite'], $params['linktitle'] );
			if ( $data === false ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-external-data' ), 'no-external-data' );
			}
			if ( isset( $data['error'] ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-client-error' ), 'client-error' );
			}

			$page = $this->titleToPage( $data, $params['linktitle'] );
			if ( isset( $page['missing'] ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-external-page' ), 'no-external-page' );
			}
			$ret = $itemContent->getItem()->addSiteLink( $params['linksite'], $page['title'], 'set' );
			if ( $ret === false ) {
				$this->dieUsage( wfMsg( 'wikibase-api-add-sitelink-failed' ), 'add-sitelink-failed' );
			}

			$this->addSiteLinksToResult( array( $ret['site'] => $ret['title'] ), 'item' );
			return $ret !== false;
		}
	}


	/**
	 * Query the external site and return the reply.
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId Identifies the external site according to the sites table.
	 * @param string $pageTitle Identifies the page at the external site, needing normalization,
	 * 		conversion and redirects.
	 *
	 * @return array Reply from the external server.
	 */
	public function queryPageAtSite( $globalSiteId, $pageTitle ) {
		// Check if we have strings as arguments.
		if ( !is_string( $globalSiteId ) || !is_string( $pageTitle ) ) {
			return false;
		}
		// Get site identifiers and figure out the URL.
		$site = Sites::singleton()->getSiteByGlobalId( $globalSiteId );
		if ( $site === false ) {
			return false;
		}
		$url = $site->getFilePath();
		if ( $site === false ) {
			return false;
		}

		// Build the args for the specific call
		$args = Settings::get( 'clientPageArgs' );
		$args['titles'] = $pageTitle;

		// Go on call the external site
		$content = $this->http_get( $url . 'api.php?' . wfArrayToCgi( $args ), $pageTitle );
		if ( $content === false ) {
			return false;
		}
		$data = json_decode( $content, true );
		return is_array( $data ) ? $data : false;
	}

	/**
	 * Do the query of the external site.
	 * Note that this makes an override if the class is under test to avoid making the test dependant
	 * on external sites. It should really be done by mock classes in the tests itself.
	 *
	 * @todo Move code for testing out of this function if possible.
	 *
	 * @since 0.1
	 *
	 * @param string $url The encoded url for the call.
	 *
	 * @return string Reply from the external server.
	 */
	public function http_get( $url, $pageTitle ) {
		// Note that the following can create inconsistencies!
		// If the code is under test, then avoid accessing external client sites
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			// Construct a "page" for the json in case we are testing, as it is very bad to access
			// external sites during testing. This will not make it possible to do complete testing
			// of all error situations, even more so than having a bit of code for testing here.
			// Note that the later Http::get is a static method.
			// The following will do an equivalent to a page query but without normalization, conversion
			// and redirects. The rest of the parsing is similar to the normal client response.
			$content = "{ \"query\" : { \"pages\" : { \"1\" : { \"title\" : \"$pageTitle\" } } } }";
		}
		// In production so go get the normalization/conversion from the external site
		else {
			// It will be nearly impossible to figure out what goes wrong without the status available,
			// the only indication is that there are no json to decode.
			$content = Http::get( $url, Settings::get( 'clientTimeout' ), Settings::get( 'clientPageOpts' ) );
		}
		if ( is_string( $content ) ) {
			if ( preg_match( '/^Waiting for [^ ]*: [0-9.-]+ seconds lagged$/', $content ) ) {
				return false;
			}
			return $content;
		}
		return false;
	}

	/**
	 * Follow the from-to pairs from the title and to the final page.
	 *
	 * @since 0.1
	 *
	 * @param array $externalData A reply from the external server, previously returned from
	 * 		a call to queryPageAtSite or through som other similar method.
	 * @param string $pageTitle Identifies the page at the external site, needing normalization,
	 * 		conversion and redirects.
	 *
	 * @return array|false Reply from the external server filtered down to a single page.
	 */
	public function titleToPage( $externalData, $pageTitle ) {
		// If there is a special case with only one returned page
		// and its not marked missing we can cheat, and only return
		// the single page in the "pages" substructure.
		if ( isset( $externalData['query']['pages'] ) ) {
			$pages = array_values( $externalData['query']['pages'] );
			if ( count( $pages) === 1 ) {
				if ( !isset( $pages[0]['missing'] ) ) {
					return $pages[0];
				}
			}
		}
		// This is only used during internal testing, as it is assumed
		// a more optimal (and lossfree) storage.
		// Make initial checks and return if prerequisites are not meet.
		if ( !is_array( $externalData ) || !isset( $externalData['query'] ) ) {
			return false;
		}
		// Loop over the tree different named structures, that otherwise are similar
		$structs = array(
			'normalized' => 'from',
			'converted' => 'from',
			'redirects' => 'from',
			'pages' => 'title'
		);
		foreach ( $structs as $listId => $fieldId ) {
			// Check if the substructure exist at all.
			if ( !isset( $externalData['query'][$listId] ) ) {
				continue;
			}
			// Filter the substructure down to what we actually are using.
			$collectedHits = array_filter(
				array_values( $externalData['query'][$listId] ),
				function( $a ) use ( $fieldId, $pageTitle ) {
					return $a[$fieldId] === $pageTitle;
				}
			);
			// If still looping over normalization, conversion or redirects,
			// then we need to keep the new page title for later rounds.
			if ( $fieldId === 'from' && is_array( $collectedHits ) ) {
				switch ( count( $collectedHits ) ) {
				case 0:
					break;
				case 1:
					$pageTitle = $collectedHits[0]['to'];
					break;
				default:
					return false;
				}
			}
			// If on the pages structure we should prepare for returning.
			elseif ( $fieldId === 'title' && is_array( $collectedHits ) ) {
				switch ( count( $collectedHits ) ) {
				case 0:
					return false;
				case 1:
					return array_shift( $collectedHits );
				default:
					return false;
				}
			}
		}
		// should never be here
		return false;
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'empty-link-title', 'info' => wfMsg( 'wikibase-api-empty-link-title' ) ),
			array( 'code' => 'link-exists', 'info' => wfMsg( 'wikibase-api-link-exists' ) ),
			array( 'code' => 'database-error', 'info' => wfMsg( 'wikibase-api-database-error' ) ),
		) );
	}

	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		$allowedParams = parent::getAllowedParams();
		$allowedParams['item'][ApiBase::PARAM_DFLT] = 'set';
		return array_merge( $allowedParams, array(
			'linksite' => array(
				ApiBase::PARAM_TYPE => Sites::singleton()->getGlobalIdentifiers(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'linktitle' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		) );
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'linksite' => 'The identifier of the site on which the article to link resides',
			'linktitle' => 'The title of the article to link',
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to associate an artiile on a wiki with a Wikibase item or remove an already made such association.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetsitelink&id=42&linksite=en&linktitle=Wikimedia'
			=> 'Add title "Wikimedia" for English page with id "42" if the site link does not exist',
			'api.php?action=wbsetsitelink&id=42&linksite=en&linktitle=Wikimedia&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Add title "Wikimedia" for English page with id "42", if the site link does not exist',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetsitelink';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
