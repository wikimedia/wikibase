<?php

namespace Wikibase\Client\RecentChanges;

use Guzzle\Common\Exception\RuntimeException;
use InvalidArgumentException;
use Language;
use Linker;
use RecentChange;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ExternalChangeFactory {

	/**
	 * @var string
	 */
	private $repoSiteId;

	/**
	 * @var Language
	 */
	private $summaryLanguage;

	/**
	 * @param string $repoSiteId
	 * @param Language $summaryLanguage Language to use when generating edit summaries
	 */
	public function __construct( $repoSiteId, Language $summaryLanguage ) {
		$this->repoSiteId = $repoSiteId;
		$this->summaryLanguage = $summaryLanguage;
	}

	/**
	 * @since 0.5
	 *
	 * @param RecentChange $recentChange
	 *
	 * @throws UnexpectedValueException
	 * @return ExternalChange
	 */
	public function newFromRecentChange( RecentChange $recentChange ) {
		$changeParams = $this->extractChangeData( $recentChange );

		$itemId = $this->extractItemId( $changeParams['object_id'] );
		$changeType = $this->extractChangeType( $changeParams['type'] );
		$rev = $this->newRevisionData( $recentChange, $changeParams );

		return new ExternalChange( $itemId, $rev, $changeType );
	}

	/**
	 * @param RecentChange $recentChange
	 * @param array $changeParams
	 *
	 * @return RevisionData
	 */
	private function newRevisionData( RecentChange $recentChange, array $changeParams ) {
		$repoId = isset( $changeParams['site_id'] )
			? $changeParams['site_id'] : $this->repoSiteId;

		// NOTE: "comment overrides" are legacy behavior.
		//@todo remove this once all old entries have cleared from the job queue and recentchanges.
		$commentOverride = $this->extractCommentOverride( $changeParams );

		return new RevisionData(
			$recentChange->getAttribute( 'rc_user_text' ),
			$changeParams['page_id'],
			$changeParams['rev_id'],
			$changeParams['parent_id'],
			$recentChange->getAttribute( 'rc_timestamp' ),
			$commentOverride ?: $recentChange->getAttribute( 'rc_comment' ),
			$repoId
		);
	}

	/**
	 * @param RecentChange $recentChange
	 *
	 * @throws UnexpectedValueException
	 * @return array
	 */
	private function extractChangeData( RecentChange $recentChange ) {
		$params = unserialize( $recentChange->getAttribute( 'rc_params' ) );

		if ( !is_array( $params ) || !array_key_exists( 'wikibase-repo-change', $params ) ) {
			throw new UnexpectedValueException( 'Not a Wikibase change' );
		}

		$changeParams = $params['wikibase-repo-change'];

		$this->validateChangeData( $changeParams );

		return $changeParams;
	}

	/**
	 * @param array $changeParams
	 *
	 * @throws UnexpectedValueException
	 * @return bool
	 */
	private function validateChangeData( $changeParams ) {
		if ( !is_array( $changeParams ) ) {
			throw new UnexpectedValueException( 'Invalid Wikibase change' );
		}

		$keys = array( 'type', 'page_id', 'rev_id', 'parent_id', 'object_id' );

		foreach ( $keys as $key ) {
			if ( !array_key_exists( $key, $changeParams ) ) {
				throw new UnexpectedValueException( "$key key missing in change data" );
			}
		}

		return true;
	}

	/**
	 * @see EntityChange::getAction
	 *
	 * @param string $type
	 *
	 * @throws UnexpectedValueException
	 * @return string
	 */
	private function extractChangeType( $type ) {
		if ( !is_string( $type ) ) {
			throw new UnexpectedValueException( '$type must be a string.' );
		}

		list( , $changeType ) = explode( '~', $type, 2 );

		return $changeType;
	}

	/**
	 * @param string $prefixedId
	 *
	 * @throws UnexpectedValueException
	 * @return ItemId
	 */
	private function extractItemId( $prefixedId ) {
		try {
			return new ItemId( $prefixedId );
		} catch ( InvalidArgumentException $ex ) {
			throw new UnexpectedValueException( 'Invalid $itemId found for change.' );
		}
	}

	/**
	 * This method transforms the comments field into rc_params into an appropriate
	 * comment value for ExternalChange.
	 *
	 * $comment can be a string or an array with some additional data.
	 *
	 * String comments are either 'wikibase-comment-update' (legacy) or have
	 * comments from the repo, such as '/ wbsetclaim-update:2||1 / [[Property:P213]]: [[Q850]]'.
	 *
	 * We don't yet parse repo comments in the client, so for now, we use the
	 * generic 'wikibase-comment-update' for these.
	 *
	 * Comment arrays may contain a message key that provide autocomments for stuff
	 * like log actions (item deletion) or edits that have no meaningful summary
	 * to use in the client.
	 *
	 *  - 'wikibase-comment-unlinked' (when the sitelink to the given page is removed on the repo)
	 *  - 'wikibase-comment-add' (when the item is created, with sitelink to the given page)
	 *  - 'wikibase-comment-remove' (when the item is deleted, the page becomes unconnected)
	 *  - 'wikibase-comment-restore' (when the item is undeleted and reconnected to the page)
	 *  - 'wikibase-comment-sitelink-add' (and other sitelink messages, unused)
	 *  - 'wikibase-comment-update' (legacy, generic, item updated commment)
	 *
	 * @param array|string $comment
	 * @param string $type
	 *
	 * @return string
	 */
	private function parseAutoComment( $comment, $type ) {
		$newComment = array(
			'key' => 'wikibase-comment-update'
		);

		if ( is_array( $comment ) ) {
			if ( $type === 'wikibase-item~add' ) {
				// @todo: provide a link to the entity
				$newComment['key'] = 'wikibase-comment-linked';
			} elseif ( array_key_exists( 'sitelink', $comment ) ) {
				// @fixme site link change message
				$newComment['key'] = 'wikibase-comment-update';
			} else {
				$newComment['key'] = $comment['message'];
			}
		}

		// @todo handle $comment values that are strings or whatever format
		// that we use to transfer autocomments from repo to client.

		return $newComment;
	}

	/**
	 * @param array $comment
	 *
	 * @return string
	 */
	private function formatComment( array $comment ) {
		$commentMsg = wfMessage( $comment['key'] )->inLanguage( $this->summaryLanguage );

		if ( isset( $comment['numparams'] ) ) {
			$commentMsg->numParams( $comment['numparams'] );
		}

		// fixme: find a way to inject or not use Linker
		return Linker::commentBlock(
			$commentMsg->text()
		);
	}

	/**
	 * @param array $changeParams
	 *
	 * @todo remove this once all old entries have cleared from the job queue and recentchanges.
	 * @return string
	 */
	private function extractCommentOverride( array $changeParams ) {
		// NOTE: We want to get rid of the comment and composite-comment fields in $changeParams
		// in the future, see https://phabricator.wikimedia.org/T101836#1414639 part 3.
		if ( array_key_exists( 'composite-comment', $changeParams ) ) {
			$comment['key'] = 'wikibase-comment-multi';
			$comment['numparams'] = $this->countCompositeComments( $changeParams['composite-comment'] );

			return $this->formatComment( $comment );
		} elseif ( array_key_exists( 'comment', $changeParams ) ) {
			$comment = $this->parseAutoComment( $changeParams['comment'], $changeParams['type'] );

			return $this->formatComment( $comment );
		} else {
			// no override
			return false;
		}
	}

	/**
	 * normalizes for extra empty comment in rc_params (see bug T47812)
	 *
	 * @param array $comments
	 *
	 * @return int
	 */
	private function countCompositeComments( array $comments ) {
		$compositeComments = array_filter( $comments );

		return count( $compositeComments );
	}

}
