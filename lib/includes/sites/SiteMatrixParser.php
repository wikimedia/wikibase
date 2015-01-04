<?php

/**
 * Translates api sitematrix results json into an array of Site objects
 *
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteMatrixParser {

	/**
	 * @var string
	 */
	private $scriptPath;

	/**
	 * @var string
	 */
	private $articlePath;

	/**
	 * @var boolean
	 */
	private $expandGroup;

	/**
	 * @var bool
	 */
	private $stripProtocol;

	/**
	 * @var string|null
	 */
	private $forceProtocol;

	/**
	 * @param string $scriptPath (e.g. '/w/$1')
	 * @param string $articlePath (e.g. '/wiki/$1')
	 * @param bool $stripProtocol
	 * @param string|null $forceProtocol
	 * @param boolean $expandGroup expands site matrix group codes from wiki to wikipedia
	 */
	public function __construct( $scriptPath, $articlePath, $stripProtocol, $forceProtocol, $expandGroup = true ) {
		$this->scriptPath = $scriptPath;
		$this->articlePath = $articlePath;
		$this->stripProtocol = $stripProtocol;
		$this->forceProtocol = $forceProtocol;
		$this->expandGroup = $expandGroup;

		if ( $this->stripProtocol && $this->forceProtocol !== null ) {
			throw new MWException( '$stripProtocol and $forceProtocol cannot be used together.' );
		}
	}

	/**
	 * @param string $json
	 *
	 * @throws InvalidArgumentException
	 * @return Site[]
	 */
	public function sitesFromJson( $json ) {
		$specials = null;

		$data = json_decode( $json, true );

		if ( !is_array( $data ) || !array_key_exists( 'sitematrix', $data ) ) {
			throw new InvalidArgumentException( 'Cannot decode site matrix data.' );
		}

		if ( array_key_exists( 'specials', $data['sitematrix'] ) ) {
			$specials = $data['sitematrix']['specials'];
			unset( $data['sitematrix']['specials'] );
		}

		if ( array_key_exists( 'count', $data['sitematrix'] ) ) {
			unset( $data['sitematrix']['count'] );
		}

		$groups = $data['sitematrix'];

		$sites = array();

		foreach( $groups as $groupData ) {
			$sites = array_merge(
				$sites,
				$this->getSitesFromLangGroup( $groupData )
			);
		}

		$sites = array_merge(
			$sites,
			$this->getSpecialSites( $specials )
		);

		return $sites;
	}

	/**
	 * @param array $specialSites
	 *
	 * @return Site[]
	 */
	private function getSpecialSites( array $specialSites ) {
		$sites = array();

		foreach( $specialSites as $specialSite ) {
			$site = $this->getSiteFromSiteData( $specialSite );
			$siteId = $site->getGlobalId();

			// todo: get this from $wgConf
			$site->setLanguageCode( 'en' );

			$sites[$siteId] = $site;
		}

		return $sites;
	}

	/**
	 * Gets an array of Site objects for all sites of the same language
	 * subdomain grouping used in the site matrix.
	 *
	 * @param array $langGroup
	 *
	 * @return Site[]
	 */
	private function getSitesFromLangGroup( $langGroup ) {
		$sites = array();

		foreach( $langGroup['site'] as $siteData ) {
			if ( !array_key_exists( 'code', $langGroup ) ) {
				continue;
			}

			$site = $this->getSiteFromSiteData( $siteData, $langGroup['code'], false );
			$site->setLanguageCode( $langGroup['code'] );
			$siteId = $site->getGlobalId();
			$sites[$siteId] = $site;
		}

		return $sites;
	}

	/**
	 * @param array $siteData
	 *
	 * @return Site
	 */
	private function getSiteFromSiteData( $siteData ) {
		$site = new MediaWikiSite();
		$site->setGlobalId( $siteData['dbname'] );

		// @note: expandGroup is specific to wikimedia site matrix sources
		$siteGroup = ( $this->expandGroup && $siteData['code'] === 'wiki' )
			? 'wikipedia' : $siteData['code'];

		$site->setGroup( $siteGroup );

		$url = $siteData['url'];

		if ( $this->stripProtocol ) {
			$url = preg_replace( '@^https?:@', '', $url );
		} elseif ( $this->forceProtocol !== null ) {
			$url = preg_replace( '@^https?:@', $this->forceProtocol . ':', $url );
		}

		$site->setFilePath( $url . $this->scriptPath );
		$site->setPagePath( $url . $this->articlePath );

		return $site;
	}

}
