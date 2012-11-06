<?php
namespace Wikibase;

/**
 * Calculates and stores score for term search
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */

class TermMatchScoreCalculator
{
	var $entry;
	var $search;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct( $entry, $search ) {
		$this->entry = $entry;
		$this->search = $search;
	}

	/**
	 * Calculate score
	 *
	 * @returns integer $score
	 */
	public function calculateScore() {
		$score = strlen( $this->search ) / strlen( $this->entry['label'] );
		foreach ( $this->entry['aliases'] as $alias ) {
			$aliasscore = strlen( $this->search ) / strlen( $alias );
			if ( $aliasscore > $score ) {
				$score = $aliasscore;
			}
		}
		return $score;
	}
}
