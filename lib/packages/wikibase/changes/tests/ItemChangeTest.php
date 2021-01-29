<?php

namespace Wikibase\Lib\Tests\Changes;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityDiffChangedAspectsFactory;
use Wikibase\Lib\Changes\ItemChange;
use Wikimedia\AtEase\AtEase;

/**
 * @covers \Wikibase\Lib\Changes\ItemChange
 * @covers \Wikibase\Lib\Changes\DiffChange
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ItemChangeTest extends EntityChangeTest {

	/**
	 * @return string
	 */
	protected function getRowClass() {
		return ItemChange::class;
	}

	public function itemChangeProvider() {
		$changes = array_filter(
			TestChanges::getChanges(),
			function( EntityChange $change ) {
				return ( $change instanceof ItemChange );
			}
		);

		$cases = array_map( function( ItemChange $change ) {
			return [ $change ];
		},
		$changes );

		return $cases;
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testGetSiteLinkDiff( ItemChange $change ) {
		$siteLinkDiff = $change->getSiteLinkDiff();
		$this->assertInstanceOf( Diff::class, $siteLinkDiff, 'getSiteLinkDiff must return a Diff' );
	}

	public function changeBackwardsCompatProvider() {
		//NOTE: Disable developer warnings that may get triggered by
		//      the B/C code path.
		AtEase::suppressWarnings();

		try {
			$cases = [];

			// --------
			// We may hit a plain diff generated by old code.
			// Make sure we can deal with that.

			$change = new ItemChange( [ 'type' => 'test' ] );
			$change->setCompactDiff( ( new EntityDiffChangedAspectsFactory() )->newEmpty() );

			$cases['plain-diff'] = [ $change ];

			// --------
			// Bug T53363: As of commit ff65735a125e, MapDiffer may generate atomic diffs for
			// substructures even in recursive mode. Make sure we can handle them
			// if we happen to load them from the database or such.

			$diff = new ItemDiff( [
				'links' => new DiffOpChange(
					[ 'foowiki' => 'X', 'barwiki' => 'Y' ],
					[ 'barwiki' => 'Y', 'foowiki' => 'X' ]
				)
			] );

			// make sure we got the right key for sitelinks
			assert( $diff->getSiteLinkDiff() !== null );

			//NOTE: ItemChange's constructor may or may not already fix the bad diff.
			$change = new ItemChange( [ 'type' => 'test' ] );
			$change->setCompactDiff( ( new EntityDiffChangedAspectsFactory() )->newFromEntityDiff( $diff ) );

			$cases['atomic-sitelink-diff'] = [ $change ];
		} finally {
			AtEase::restoreWarnings();
		}

		return $cases;
	}

	/**
	 * @dataProvider changeBackwardsCompatProvider
	 */
	public function testGetSiteLinkDiffBackwardsCompat( ItemChange $change ) {
		//NOTE: Disable developer warnings that may get triggered by
		//      the B/C code path.
		AtEase::suppressWarnings();

		try {
			$siteLinkDiff = $change->getSiteLinkDiff();
			$this->assertInstanceOf(
				Diff::class,
				$siteLinkDiff,
				'getSiteLinkDiff must return a Diff'
			);
		} finally {
			AtEase::restoreWarnings();
		}
	}

}
