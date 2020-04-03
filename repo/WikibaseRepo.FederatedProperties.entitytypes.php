<?php

/**
 * Definition of entity service overrides for Federated Properties.
 * The array returned by the code below is supposed to be merged with the content of
 * repo/WikibaseRepo.entitytypes.php.
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @see docs/entiytypes.wiki
 *
 * @license GPL-2.0-or-later
 */

use Wikibase\Lib\Store\EntityArticleIdNullLookup;
use Wikibase\Repo\FederatedProperties\ApiEntityNamespaceInfoLookup;
use Wikibase\Repo\FederatedProperties\ApiEntityTitleTextLookup;
use Wikibase\Repo\FederatedProperties\ApiEntityUrlLookup;
use Wikibase\Repo\FederatedProperties\ApiEntitySearchHelper;
use Wikibase\Repo\WikibaseRepo;

return [
	'property' => [
		'article-id-lookup-callback' => function () {
			return new EntityArticleIdNullLookup();
		},
		'url-lookup-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new ApiEntityUrlLookup(
				new ApiEntityTitleTextLookup(
					new ApiEntityNamespaceInfoLookup(
						$wikibaseRepo->newFederatedPropertiesApiClient(),
						$wikibaseRepo->getContentModelMappings()
					)
				),
				$wikibaseRepo->getSettings()->getSetting( 'federatedPropertiesSourceScriptUrl' )
			);
		},
		'title-text-lookup-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new ApiEntityTitleTextLookup(
				new ApiEntityNamespaceInfoLookup(
					$wikibaseRepo->newFederatedPropertiesApiClient(),
					$wikibaseRepo->getContentModelMappings()
				)
			);
		},
		'entity-search-callback' => function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new ApiEntitySearchHelper(
				$wikibaseRepo->newFederatedPropertiesApiClient()
			);
		},
	]
];
