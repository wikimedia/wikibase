<?php

namespace Wikibase\Client\RecentChanges;

use Content;
use InvalidArgumentException;
use Language;
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
		$rc_params = $recentChange->parseParams();

		if ( !is_array( $rc_params ) || !array_key_exists( 'wikibase-repo-change', $rc_params ) ) {
			throw new UnexpectedValueException( 'Not a Wikibase change' );
		}

		$changeParams = $this->extractChangeData( $rc_params );

		// If a pre-formatted comment exists, pass it on.
		$changeHtml = isset( $rc_params['comment-html'] ) ? $rc_params['comment-html'] : null;

		$itemId = $this->extractItemId( $changeParams['object_id'] );
		$changeType = $this->extractChangeType( $changeParams['type'] );
		$rev = $this->newRevisionData( $recentChange, $changeParams, $changeHtml );

		return new ExternalChange( $itemId, $rev, $changeType );
	}

	/**
	 * @param RecentChange $recentChange
	 * @param array $changeParams
	 * @param string|null $commentHtml Pre-formatted comment HTML
	 *
	 * @return RevisionData
	 */
	private function newRevisionData( RecentChange $recentChange, array $changeParams, $commentHtml = null ) {
		$repoId = isset( $changeParams['site_id'] )
			? $changeParams['site_id'] : $this->repoSiteId;

		$comment = $recentChange->getAttribute( 'rc_comment' );

		if ( $comment === '' || $comment === null ) {
			$comment = $this->generateComment( $changeParams );
		}

		return new RevisionData(
			$recentChange->getAttribute( 'rc_user_text' ),
			$changeParams['page_id'],
			$changeParams['rev_id'],
			$changeParams['parent_id'],
			$recentChange->getAttribute( 'rc_timestamp' ),
			$comment,
			$commentHtml,
			$repoId
		);
	}

	/**
	 * @param array Content of rc_params
	 *
	 * @throws UnexpectedValueException
	 * @return array
	 */
	private function extractChangeData( array $rc_params ) {
		$changeParams = $rc_params['wikibase-repo-change'];

		$this->validateChangeData( $changeParams );

		return $changeParams;
	}

	/**
	 * @param mixed $changeParams
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

		return $commentMsg->text();
	}

	/**
	 * @param array $changeParams
	 *
	 * @return string
	 */
	private function generateComment( array $changeParams ) {
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
