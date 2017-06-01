<?php

namespace Wikibase\Repo\Hooks;

use Html;
use IContextSource;
use SiteLookup;
use Title;
use Wikibase\Store\SubscriptionLookup;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class InfoActionHookHandler {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $namespaceChecker;

	/**
	 * @var SubscriptionLookup
	 */
	private $subscriptionLookup;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var IContextSource
	 */
	private $context;

	public function __construct(
		EntityNamespaceLookup $namespaceChecker,
		SubscriptionLookup $subscriptionLookup,
		SiteLookup $siteLookup,
		EntityIdLookup $entityIdLookup,
		IContextSource $context
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->subscriptionLookup = $subscriptionLookup;
		$this->siteLookup = $siteLookup;
		$this->entityIdLookup = $entityIdLookup;
		$this->context = $context;
	}

	/**
	 * @param IContextSource $context
	 * @param array $pageInfo
	 *
	 * @return array
	 */
	public function handle( IContextSource $context, array $pageInfo ) {
		// Check if wikibase namespace is enabled
		$title = $context->getTitle();

		if ( $this->namespaceChecker->isEntityNamespace( $title->getNamespace() ) && $title->exists() ) {
			$pageInfo['header-properties'][] = $this->getPageInfoRow( $title );
		}

		return $pageInfo;
	}

	/**
	 * @param Title $title
	 *
	 * @return string[] HTML
	 */
	private function getPageInfoRow( Title $title ) {
		$entity = $this->entityIdLookup->getEntityIdForTitle( $title );
		$subscriptions = $this->subscriptionLookup->getSubscribers( $entity );

		if ( $subscriptions ) {
			return $this->formatSubscriptions( $subscriptions, $title );
		}

		return $this->getNoSubscriptionText();
	}

	/**
	 * @param string[] $subscriptions
	 * @param Title $title
	 *
	 * @return string[] HTML
	 */
	private function formatSubscriptions( array $subscriptions, Title $title ) {
		$output = '';

		foreach ( $subscriptions as $subscription ) {
			$link = $this->formatSubscription( $subscription, $title );
			$output .= Html::rawElement( 'li', [], $link );

		}
		$output = Html::rawElement( 'ul', [], $output );
		return [ $this->context->msg( 'wikibase-pageinfo-subscription' )->parse(), $output ];
	}

	/**
	 * @return string[] HTML
	 */
	private function getNoSubscriptionText() {
		return [
			$this->context->msg( 'wikibase-pageinfo-subscription' )->parse(),
			$this->context->msg( 'wikibase-pageinfo-subscription-none' )->parse()
		];
	}

	/**
	 * @param string $subscription
	 * @param Title $title
	 *
	 * @return string HTML
	 */
	private function formatSubscription( $subscription, Title $title ) {
		$site = $this->siteLookup->getSite( $subscription );
		if ( $site === null ) {
			return $subscription;
		}

		$url = $site->getPageUrl( 'Special:EntityUsage/' . $title->getText() );
		if ( $url === false ) {
			return $subscription;
		}

		return Html::element( 'a',
			[ 'href' => $url ],
			$subscription
		);
	}

}
