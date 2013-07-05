<?php

namespace Wikibase\Test;

use Wikibase\Lib\Serializers\MultiLangSerializationOptions;
use Wikibase\Lib\Serializers\LabelSerializer;

/**
 * @covers Wikibase\Lib\Serializers\LabelSerializer
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
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 * @group WikibaseLabelSerializer
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class LabelSerializerTest extends \PHPUnit_Framework_TestCase {

	public function validProvider() {
		$validArgs = array();

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( true );
		$labels = array(
			"en" => "Rome",
			"de" => "Rom",
			"it" => "",
			"fi" => "Rooma",
		);
		$expectedSerialization = array(
			"en" => array(
				"language" => "en",
				"value" => "Rome"
			),
			"de" => array(
				"language" => "de",
				"value" => "Rom"
			),
			"it" => array(
				"language" => "it",
				"removed" => ""
			),
			"fi" => array(
				"language" => "fi",
				"value" => "Rooma"
			),
		);
		$validArgs[] = array( $labels, $options, $expectedSerialization );

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( false );
		$labels = array(
			"en" => "Rome",
			"de" => "Rom",
			"it" => "Roma",
			"fi" => "Rooma",
		);
		$expectedSerialization = array(
			array(
				"language" => "en",
				"value" => "Rome"
			),
			array(
				"language" => "de",
				"value" => "Rom"
			),
			array(
				"language" => "it",
				"value" => "Roma"
			),
			array(
				"language" => "fi",
				"value" => "Rooma"
			),
			"_element" => "label",
		);
		$validArgs[] = array( $labels, $options, $expectedSerialization );

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( true );
		$labels = array(
			"en" => "Rome",
			"de-formal" => array(
				"value" => "Rom",
				"language" => "de",
				"source" => null,
			),
			"it" => "",
			"zh-tw" => array(
				"value" => "羅馬",
				"language" => "zh-tw",
				"source" => "zh-cn",
			),
			"sr-ec" => array(
				"value" => "Rome",
				"language" => "en",
				"source" => "en",
			),
		);
		$expectedSerialization = array(
			"en" => array(
				"language" => "en",
				"value" => "Rome"
			),
			"de-formal" => array(
				"language" => "de",
				"value" => "Rom"
			),
			"it" => array(
				"language" => "it",
				"removed" => ""
			),
			"zh-tw" => array(
				"language" => "zh-tw",
				"source-language" => "zh-cn",
				"value" => "羅馬"
			),
			"sr-ec" => array(
				"language" => "en",
				"source-language" => "en",
				"value" => "Rome"
			),
		);
		$validArgs[] = array( $labels, $options, $expectedSerialization );

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( false );
		$descriptions = array(
			"en" => "Rome",
			"de-formal" => array(
				"value" => "Rom",
				"language" => "de",
				"source" => null,
			),
			"it" => "",
			"zh-tw" => array(
				"value" => "羅馬",
				"language" => "zh-tw",
				"source" => "zh-cn",
			),
			"sr-ec" => array(
				"value" => "Rome",
				"language" => "en",
				"source" => "en",
			),
		);
		$expectedSerialization = array(
			array(
				"language" => "en",
				"value" => "Rome"
			),
			array(
				"language" => "de",
				"for-language" => "de-formal",
				"value" => "Rom"
			),
			array(
				"language" => "it",
				"removed" => ""
			),
			array(
				"language" => "zh-tw",
				"source-language" => "zh-cn",
				"value" => "羅馬"
			),
			array(
				"language" => "en",
				"source-language" => "en",
				"for-language" => "sr-ec",
				"value" => "Rome"
			),
			"_element" => "label",
		);
		$validArgs[] = array( $descriptions, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testGetSerialized( $labels, $options, $expectedSerialization ) {
		$labelSerializer = new LabelSerializer( $options );
		$serializedLabels = $labelSerializer->getSerialized( $labels );

		$this->assertEquals( $expectedSerialization, $serializedLabels );
	}

	public function invalidProvider() {
		$invalidArgs = array();

		$invalidArgs[] = array( 'foo' );
		$invalidArgs[] = array( 42 );

		return $invalidArgs;
	}

	/**
	 * @dataProvider invalidProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidGetSerialized( $labels ) {
		$labelSerializer = new LabelSerializer();
		$serializedLabels = $labelSerializer->getSerialized( $labels );
	}
}
