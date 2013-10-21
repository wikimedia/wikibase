<?php

namespace Wikibase;

use Html;
use Diff\Diff;
use Diff\DiffOp;
use IContextSource;
use MWException;
use SiteSQLStore;

/**
 * Class for generating views of DiffOp objects.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 */
class DiffView extends \ContextSource {

	/**
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $path;

	/**
	 * @since 0.1
	 *
	 * @var Diff
	 */
	protected $diff;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param array $path
	 * @param Diff $diff
	 * @param IContextSource|null $contextSource
	 */
	public function __construct( array $path, Diff $diff, IContextSource $contextSource = null ) {
		$this->path = $path;
		$this->diff = $diff;

		if ( !is_null( $contextSource ) ) {
			$this->setContext( $contextSource );
		}
	}

	/**
	 * Builds and returns the HTML to represent the Diff.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHtml() {
		return $this->generateOpHtml( $this->path, $this->diff );
	}

	/**
	 * Does the actual work.
	 *
	 * @since 0.1
	 *
	 * @param array $path
	 * @param DiffOp $op
	 *
	 * @return string
	 * @throws MWException
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			$html = $this->generateDiffHeaderHtml( implode( ' / ', $path ) );

			//TODO: no path, but localized section title

			//FIXME: complex objects as values?
			if ( $op->getType() === 'add' ) {
				$html .= $this->generateAddOpHtml( $op->getNewValue(), $path );
			} elseif ( $op->getType() === 'remove' ) {
				$html .= $this->generateRemoveOpHtml( $op->getOldValue(), $path );
			} elseif ( $op->getType() === 'change' ) {
				$html .= $this->generateChangeOpHtml( $op->getOldValue(), $op->getNewValue(), $path );
			} else {
				throw new MWException( 'Invalid diffOp type' );
			}
		} else {
			$html = '';
			foreach ( $op as $key => $subOp ) {
				$html .= $this->generateOpHtml(
					array_merge( $path, array( $key ) ),
					$subOp
				);
			}
		}

		return $html;
	}

	/**
	 * Generates HTML for an add diffOp
	 *
	 * @since 0.4
	 *
	 * @param string $value
	 * @param array $path
	 *
	 * @return string
	 */
	protected function generateAddOpHtml( $value, $path ) {
		if( $path[0] === 'links' ){
			$siteStore = SiteSQLStore::newInstance();
			$siteLink = new SiteLink( $siteStore->getSite( $path[1] ), $value );
			$innerElement = Html::rawElement( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
				Html::element( 'a', array( 'href' => $siteLink->getUrl() ), $value )
			);
		} else {
			$innerElement = Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ), $value );
		}

		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(), $innerElement ) );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an remove diffOp
	 *
	 * @since 0.4
	 *
	 * @param string $value
	 * @param array $path
	 *
	 * @return string
	 */
	protected function generateRemoveOpHtml( $value, $path ) {
		if( $path[0] === 'links' ){
			$siteStore = SiteSQLStore::newInstance();
			$siteLink = new SiteLink( $siteStore->getSite( $path[1] ), $value );
			$innerElement = Html::rawElement( 'del', array( 'class' => 'diffchange diffchange-inline' ),
				Html::element( 'a', array( 'href' => $siteLink->getUrl() ), $value )
			);
		} else {
			$innerElement = Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ), $value );
		}

		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(), $innerElement ) );
		$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an change diffOp
	 *
	 * @since 0.4
	 *
	 * @param string $oldValue
	 * @param string $newValue
	 * @param array $path
	 *
	 * @return string
	 */
	protected function generateChangeOpHtml( $oldValue, $newValue, $path ) {
		if( $path[0] === 'links' ){
			$siteStore = SiteSQLStore::newInstance();
			$siteLink = new SiteLink( $siteStore->getSite( $path[1] ), $newValue );
			$innerElementIns = Html::rawElement( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
				Html::element( 'a', array( 'href' => $siteLink->getUrl() ), $newValue )
			);
		} else {
			$innerElementIns = Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ), $value );
		}

		if( $path[0] === 'links' ){
			$siteStore = SiteSQLStore::newInstance();
			$siteLink = new SiteLink( $siteStore->getSite( $path[1] ), $oldValue );
			$innerElementDel = Html::rawElement( 'del', array( 'class' => 'diffchange diffchange-inline' ),
				Html::element( 'a', array( 'href' => $siteLink->getUrl() ), $oldValue )
			);
		} else {
			$innerElementDel = Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ), $value );
		}

		//TODO: use WordLevelDiff!
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(), $innerElementDel ) );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(), $innerElementIns ) );
		$html .= Html::closeElement( 'tr' );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for the header of the diff operation
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function generateDiffHeaderHtml( $name ) {
		$html = Html::openElement( 'tr' );
		$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}
}
