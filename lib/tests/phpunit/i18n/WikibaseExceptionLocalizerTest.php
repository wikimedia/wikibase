<?php

namespace Wikibase\Test;
use Exception;
use RuntimeException;
use ValueParsers\ParseException;
use Wikibase\i18n\WikibaseExceptionLocalizer;

/**
 * @covers Wikibase\i18n\WikibaseExceptionLocalizer
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseExceptionLocalizerTest extends \PHPUnit_Framework_TestCase {

	public function provideGetExceptionMessage() {
		return array(
			'RuntimeException' => array( new RuntimeException( 'Oops!' ), 'wikibase-error-unexpected', array( 'Oops!' ) ),
			'ParseException' => array( new ParseException( 'Blarg!' ), 'wikibase-error-parse', array() ),
			//TODO: test ChangeOpValidationException with Error objects
		);
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, $expectedParams ) {
		$localizer = new WikibaseExceptionLocalizer();

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists() );
		$this->assertEquals( $expectedKey, $message->getKey() );
		$this->assertEquals( $expectedParams, $message->getParams() );
	}

}