<?php

namespace Wikibase\Lib\Test;

use ValueParsers\Test\StringValueParserTest;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdValueParser;

/**
 * @covers Wikibase\Lib\EntityIdValueParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group EntityIdValueParserTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdValueParserTest extends StringValueParserTest {

	/**
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		return 'Wikibase\Lib\EntityIdValueParser';
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return EntityIdValueParser
	 */
	protected function getInstance() {
		return new EntityIdValueParser();
	}

	/**
	 * @see ValueParserTestBase::parseProvider
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = array();

		$parser = $this->getInstance();

		$valid = array(
			'q1' => new EntityIdValue( new ItemId( 'q1' ) ),
			'p1' => new EntityIdValue( new PropertyId( 'p1' ) ),
		);

		foreach ( $valid as $value => $expected ) {
			$argLists[] = array( $value, $expected, $parser );
		}

		return array_merge( $argLists );
	}

	public function invalidInputProvider() {
		$argLists = parent::invalidInputProvider();

		$invalid = array(
			'foo',
			'c2',
			'a-1',
			'1a',
			'a1a',
			'01a',
			'a 1',
			'a1 ',
			' a1',
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}
