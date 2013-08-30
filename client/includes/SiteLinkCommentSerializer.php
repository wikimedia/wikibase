<?php
namespace Wikibase;

use Site;
use SiteList;
use Diff\Diff;
use Wikibase\Client\WikibaseClient;

/**
 * @since 0.5
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCommentSerializer {

	/**
	 * @var string
	 */
	protected $siteId;

	/**
	 * @param Site $site
	 */
	public function __construct( $siteId ) {
		$this->siteId = $siteId;
	}

	/**
	 * Returns the comment to use in RC and history entries for this change.
	 * This may be a complex structure. It will be interpreted by
	 *
	 * @since 0.4
	 *
	 * @param Diff $siteLinkDiff
	 * @param string $action
	 * @param string $comment
	 *
	 * @return array|string
	 */
	public function getEditComment( $siteLinkDiff, $action, $comment ) {
		if ( $siteLinkDiff !== null && !$siteLinkDiff->isEmpty() ) {
			return $this->getSiteLinkComment( $action, $siteLinkDiff );
		}

		return $comment;
	}

	/**
	 * Returns an array structure suitable for building an edit summary for the respective
	 * change to site links.
	 *
	 * @since 0.4
	 *
	 * @param string $action Change action
	 * @param Diff $siteLinkDiff The change's site link diff
	 *
	 * @return array|null
	 */
	protected function getSiteLinkComment( $action, Diff $siteLinkDiff ) {
		$params = null;

		if ( $siteLinkDiff->isEmpty() ) {
			return null;
		}

		//TODO: Implement comments specific to the affected page.
		//       Different pages may be affected in different ways by the same change.
		//       Also, merged changes may affect the same page in multiple ways.

		$params = array();

		$diffOps = $siteLinkDiff->getOperations();
		$siteId = $this->siteId;

		// change involved site link to client wiki
		if ( array_key_exists( $siteId, $diffOps ) ) {
			$diffOp = $diffOps[$siteId]['name'];
			$params = $this->getCommentParams( $diffOp, $action, $siteId );
		} else {
			$diffOpCount = count( $diffOps );
			if ( $diffOpCount === 1 ) {
				$params = $this->getSiteLinkChangeComment( $diffOps );
			} else {
				// @todo report how many changes
				$params = array(
					'message' => 'wikibase-comment-update'
				);
			}
		}

		return $params;
	}

	protected function getSiteLinkChangeComment( $diffOps ) {
		$messagePrefix = 'wikibase-comment-sitelink-';
		/* Messages used:
			wikibase-comment-sitelink-add wikibase-comment-sitelink-change wikibase-comment-sitelink-remove
		*/
		$params['message'] = $messagePrefix . 'change';

		foreach( $diffOps as $siteKey => $diff ) {
			if ( !array_key_exists( 'name', $diff ) ) {
				continue;
			}

			$diffOp = $diff['name'];

			if ( $diffOp instanceof \Diff\DiffOpAdd ) {
				$params['message'] = $messagePrefix . 'add';
				$params['sitelink'] = array(
					'newlink' => array(
						'site' => $siteKey,
						'page' => $diffOp->getNewValue()
					)
				);
			} elseif ( $diffOp instanceof \Diff\DiffOpRemove ) {
				$params['message'] = $messagePrefix . 'remove';
				$params['sitelink'] = array(
					'oldlink' => array(
						'site' => $siteKey,
						'page' => $diffOp->getOldValue()
					)
				);
			} elseif ( $diffOp instanceof \Diff\DiffOpChange ) {
				$params['sitelink'] = array(
					'oldlink' => array(
						'site' => $siteKey,
						'page' => $diffOp->getOldValue()
					),
					'newlink' => array(
						'site' => $siteKey,
						'page' => $diffOp->getNewValue()
					)
				);
			}
			// @todo: because of edit conflict bug in repo
			// sometimes we get multiple stuff in diffOps
			break;
		}

		return $params;
	}

	protected function getCommentParams( $diffOp, $action, $siteGlobalId ) {
		$params = array();

		if ( in_array( $action, array( 'remove', 'restore' ) ) ) {
			$params['message'] = 'wikibase-comment-' . $action;
		} elseif ( $diffOp instanceof \Diff\DiffOpAdd ) {
			$params['message'] = 'wikibase-comment-linked';
		} elseif ( $diffOp instanceof \Diff\DiffOpRemove ) {
			$params['message'] = 'wikibase-comment-unlink';
		} elseif ( $diffOp instanceof \Diff\DiffOpChange ) {
			$params['message'] = 'wikibase-comment-sitelink-change';

			// FIXME: this code appears to be doing something incorrect as "best effort"
			// rather than allowing for proper error handling
			$params['sitelink'] = array(
				'oldlink' => array(
					'site' => $siteGlobalId,
					'page' => $diffOp->getOldValue()
				),
				'newlink' => array(
					'site' => $siteGlobalId,
					'page' => $diffOp->getNewValue()
				)
			);
		}

		return $params;
	}
}
