<?php

namespace Wikibase;

/**
 * Implementation of the References interface.
 * @see References
 *
 * Note that this implementation is based on SplObjectStorage and
 * is not enforcing the type of objects set via it's native methods.
 * Therefore one can add non-Reference-implementing objects when
 * not sticking to the methods of the References interface.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceList extends \SplObjectStorage implements References {

	/**
	 * @see References::addReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function addReference( Reference $reference ) {
		$this->attach( $reference );
	}

	/**
	 * @see References::hasReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return boolean
	 */
	public function hasReference( Reference $reference ) {
		return $this->contains( $reference );
	}

	/**
	 * @see References::removeReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function removeReference( Reference $reference ) {
		$this->detach( $reference );
	}

	/**
	 * @see References::toArray
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function toArray() {
		$references = array();

		/**
		 * @var Reference $reference
		 */
		foreach ( $this as $reference ) {
			$references[] = $reference->getSnaks()->toArray();
		}

		return $references;
	}

	/**
	 * Factory for constructing a ReferenceList from its array representation.
	 *
	 * @since 0.3
	 *
	 * @param array $data
	 *
	 * @return References
	 */
	public static function newFromArray( array $data ) {
		$references = array();

		foreach ( $data as $snaks ) {
			$snaks[] = new ReferenceObject( SnakList::newFromArray( $snaks ) );
		}

		return new static( $references );
	}

	/**
	 * The hash is purely valuer based. Order of the elements in the array is not held into account.
	 *
	 * Note: we cannot implement Hashable interface by having this be getHash since PHP 5.4
	 * introduced a similarly named method in SplObjectStorage.
	 *
	 * @since 0.3
	 *
	 * @internal param MapHasher $mapHasher
	 *
	 * @return string
	 */
	public function getValueHash() {
		// We cannot have this as optional arg, because then we're no longer
		// implementing the Hashable interface properly according to PHP...
		$args = func_get_args();

		/**
		 * @var MapHasher $hasher
		 */
		$hasher = array_key_exists( 0, $args ) ? $args[0] : new MapValueHasher();

		return $hasher->hash( $this );
	}

	/**
	 * @see Comparable::equals
	 *
	 * The comparison is done purely value based, ignoring the order of the elements in the array.
	 *
	 * @since 0.3
	 *
	 * @param mixed $mixed
	 *
	 * @return boolean
	 */
	public function equals( $mixed ) {
		return is_object( $mixed )
			&& $mixed instanceof ReferenceList
			&& $this->getValueHash() === $mixed->getValueHash();
	}

}
