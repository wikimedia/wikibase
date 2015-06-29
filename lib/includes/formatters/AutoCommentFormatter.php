<?php

namespace Wikibase\Lib;

use DataValues\DataValue;
use Language;

/**
 * Formatter for machine-readable autocomments as generated by SummaryFormatte in the repo.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 *
 * @author Brad Jorsch
 * @author Thiemo Mättig
 * @author Tobias Gritschacher
 * @author Daniel Kinzler
 */
class AutoCommentFormatter {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var string
	 */
	private $messagePrefix;

	/**
	 * @param Language $language
	 * @param string $messagePrefix
	 */
	public function __construct( Language $language, $messagePrefix ) {
		$this->language = $language;
		$this->messagePrefix = $messagePrefix;
	}

	/**
	 * Pretty formatting of autocomments.
	 *
	 * @warning This method is used to parse and format autocomment strings from
	 * the revision history. It should remain compatible with any old autocomment
	 * strings that may be in the database.
	 *
	 * @param string|false $pre content before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param string|false $post content after the autocomment
	 *
	 * @return string|null
	 */
	public function formatAutoComment( $pre, $auto, $post ) {
		if ( !preg_match( '/^([a-z\-]+)\s*(:\s*(.*?)\s*)?$/', $auto, $matches ) ) {
			return null;
		}

		// turn the args to the message into an array
		$args = isset( $matches[3] ) ? explode( '|', $matches[3] ) : array();

		// look up the message
		$msg = wfMessage( $this->messagePrefix . '-summary-' . $matches[1] );

		if ( !$msg->exists() || $msg->isDisabled() ) {
			return null;
		}

		// parse the autocomment
		$auto = $msg->params( $args )->parse();

		// add pre and post fragments
		if ( $pre === true ) {
			// written summary $presep autocomment (summary /* section */)
			$pre = wfMessage( 'autocomment-prefix' )->escaped();
		} elseif ( $pre !== '' && $pre !== false ) {
			// written summary $presep autocomment (summary /* section */)
			$pre .= wfMessage( 'autocomment-prefix' )->escaped();
		} elseif ( $pre === false ) {
			$pre = '';
		}
		if ( $post !== '' && $post !== false ) {
			// autocomment $postsep written summary (/* section */ summary)
			$auto .= wfMessage( 'colon-separator' )->escaped();
			if ( $post === true ) {
				$post = '';
			}
		} elseif ( $post === false ) {
			$post = '';
		}

		$auto = '<span class="autocomment">' . $auto . '</span>';
		$comment = $pre . $this->language->getDirMark() . '<span dir="auto">' . $auto . '</span>' . $post;

		return $comment;
	}

}
