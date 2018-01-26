<?php

namespace Wikibase\Repo\Tests\Parsers;

use Language;
use Wikibase\Lib\MediaWikiNumberLocalizer;
use Wikibase\Repo\Parsers\MediaWikiNumberUnlocalizer;

/**
 * @covers Wikibase\Lib\MediaWikiNumberLocalizer
 * @covers Wikibase\Repo\Parsers\MediaWikiNumberUnlocalizer
 *
 * @group ValueParsers
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MediaWikiNumberUnlocalizerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return array[] Array of arrays of three strings: localized value, language code and expected
	 * canonical value
	 */
	public function provideUnlocalize() {
		return [
			[ '1', 'en', '1' ],
			[ '-1.1', 'en', '-1.1' ],

			[ '-1.234,56', 'de', '-1234.56' ],

			[ "\xe2\x88\x921.234,56", 'de', '-1234.56' ],
			[ "\xe2\x93\x961.234,56", 'de', '-1234.56' ],
			[ "\xe2\x93\x951.234,56", 'de', '+1234.56' ],

			[ "1\xc2\xa0234,56", 'sv', '1234.56' ],
			[ "1 234,56", 'sv', '1234.56' ],
		];
	}

	/**
	 * @dataProvider provideUnlocalize
	 */
	public function testUnlocalize( $localized, $languageCode, $canonical ) {
		$language = Language::factory( $languageCode );
		$unlocalizer = new MediaWikiNumberUnlocalizer( $language );

		$unlocalized = $unlocalizer->unlocalizeNumber( $localized );

		$this->assertEquals( $canonical, $unlocalized );
	}

	/**
	 * @return array[] Array of arrays of two or three values: number, language code and optional
	 * expected canonical value
	 */
	public function provideLocalizationRoundTrip() {
		$numbers = [ 12, -4.111, 12345678 ];
		$languages = [
			'en', 'es', 'pt', 'fr', 'de', 'sv', 'ru',  // western arabic numerals, but different separators
			'ar', 'fa', 'my', 'pi', 'ne', 'kn', // different numerals
		];

		$cases = [];
		foreach ( $languages as $lang ) {
			foreach ( $numbers as $num ) {
				$cases[] = [ $num, $lang ];
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider provideLocalizationRoundTrip
	 */
	public function testLocalizationRoundTrip( $number, $languageCode, $canonical = null ) {
		if ( $canonical === null ) {
			$canonical = "$number";
		}

		$language = Language::factory( $languageCode );

		$localizer = new MediaWikiNumberLocalizer( $language );
		$unlocalizer = new MediaWikiNumberUnlocalizer( $language );

		$localized = $localizer->localizeNumber( $number );
		$unlocalized = $unlocalizer->unlocalizeNumber( $localized );

		$this->assertEquals( $canonical, $unlocalized );
	}

	/**
	 * @return array[] Array of arrays of one or two strings: value and optional language code
	 */
	public function provideGetNumberRegexMatch() {
		return [
			[ '5' ],
			[ '+3' ],
			[ '-15' ],

			[ '5.3' ],
			[ '+3.2' ],
			[ '-15.77' ],

			[ '.3' ],
			[ '+.2' ],
			[ '-.77' ],

			[ '3e9' ],
			[ '3.1E-9' ],
			[ '-.7E+3' ],

			[ '3x10^9' ],
			[ '3.1x10^-9' ],
			[ '-.7x10^+3' ],

			[ '1,335.3' ],
			[ '+1,333.2' ],
			[ '-1,315.77' ],

			[ '12.345,77', 'de' ],
			[ "12\xc2\xa0345,77", 'sv' ], // non-breaking space, as generated by the formatter
			[ "12 345,77", 'sv' ], // regular space, as might be entered by users

			[ "1\xc2\xa0234.56", 'la' ], // incomplete separatorTransformTable
		];
	}

	/**
	 * @dataProvider provideGetNumberRegexMatch
	 */
	public function testGetNumberRegexMatch( $value, $lang = 'en' ) {
		$lang = Language::factory( $lang );
		$unlocalizer = new MediaWikiNumberUnlocalizer( $lang );
		$regex = $unlocalizer->getNumberRegex();

		$hex = bin2hex( $regex );

		$match = (bool)preg_match( "/^(?:$regex)$/u", $value, $m );
		$this->assertTrue( $match, "Hex $value: $hex" );
		$this->assertCount( 1, $m, 'There should be no capturing groups' );
	}

	/**
	 * @return array[] Array of arrays of one or two strings: value and optional language code
	 */
	public function provideGetNumberRegexMismatch() {
		return [
			[ '' ],
			[ ' ' ],
			[ '+' ],
			[ 'e' ],
			[ '123+456' ],

			[ '.-' ],

			[ '0x20' ],
			[ '2x2' ],
			[ 'x2' ],
			[ '2x' ],

			[ 'e.' ],
			[ '.e' ],
			[ '12e' ],
			[ '12e-' ],
			[ '12e,' ],
			[ 'E17' ],
			[ '2E+-2' ],
			[ '2e2.3' ],
			[ '2e3e4' ],

			[ 'x10^' ],
			[ '.x10^' ],
			[ '12x10^' ],
			[ '12x10^-' ],
			[ '12x10^,' ],
			[ 'x10^17' ],
			[ '2x10^+-2' ],
			[ '2x10^2.3' ],
			[ '2x10^3x10^4' ],

			[ '+-3' ],
			[ '++7' ],
			[ '--5' ],
		];
	}

	/**
	 * @dataProvider provideGetNumberRegexMismatch
	 */
	public function testGetNumberRegexMismatch( $value, $lang = 'en' ) {
		$unlocalizer = new MediaWikiNumberUnlocalizer( Language::factory( $lang ) );
		$regex = $unlocalizer->getNumberRegex();

		$this->assertFalse( (bool)preg_match( "/^($regex)$/u", $value ) );
	}

}
