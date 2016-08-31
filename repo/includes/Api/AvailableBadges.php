<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiResult;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to query available badge items.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AvailableBadges extends ApiBase {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.5
	 */
	public function execute() {
		$this->getMain()->setCacheMaxAge( 3600 );

		$badgeItems = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'badgeItems' );
		$idStrings = array_keys( $badgeItems );
		ApiResult::setIndexedTagName( $idStrings, 'badge' );
		$this->getResult()->addValue(
			null,
			'badges',
			$idStrings
		);
	}

	/**
	 * @see ApiBase::getCacheMode
	 *
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( array $params ) {
		return 'public';
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbavailablebadges' =>
				'apihelp-wbavailablebadges-example-1',
		);
	}

}
