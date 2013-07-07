<?php

namespace Wikibase\Test;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;

/**
 * Tests for the Wikibase\LanguageFallbackChain class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 */
class LanguageFallbackChainTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseLib
	 * @dataProvider provideExtractPreferredValue
	 */
	public function testExtractPreferredValue( $lang, $mode, $data, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->newFromLanguageCode( $lang, $mode );

		$resolved = $chain->extractPreferredValue( $data );

		$this->assertEquals( $expected, $resolved );
	}

	public function provideExtractPreferredValue() {
		$data = array(
			'en' => 'foo',
			'nl' => 'bar',
			'zh-cn' => '测试',
			'lzh' => '試',
			'zh-classical' => '驗',
		);

		return array(
			array( 'en', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => 'foo',
				'language' => 'en',
				'source' => 'en',
			) ),
			array( 'zh-classical', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => '試',
				'language' => 'lzh',
				'source' => 'lzh',
			) ),
			array( 'nl', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => 'bar',
				'language' => 'nl',
				'source' => 'nl',
			) ),
			array( 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $data, null ),
			array( 'de', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => 'foo',
				'language' => 'en',
				'source' => 'en',
			) ),
			array( 'zh', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => '测试',
				'language' => 'zh',
				'source' => 'zh-cn',
			) ),
			array( 'zh-tw', LanguageFallbackChainFactory::FALLBACK_SELF, $data, null ),
			array( 'zh-tw', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => '測試',
				'language' => 'zh-tw',
				'source' => 'zh-cn',
			) ),
			array(
				'zh-tw',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				array(
					'value' => '測試',
					'language' => 'zh-tw',
					'source' => 'zh-cn',
				),
			),
			array(
				'sr-ec',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				null,
			),
			array( 'sr-ec', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				// Shouldn't be converted to Cyrillic ('фоо') as this specific
				// value ('foo') is taken from the English label.
				'value' => 'foo',
				'language' => 'en',
				'source' => 'en',
			) ),
			array(
				'gan-hant',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				null,
			),
			array( 'gan-hant', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => '測試',
				'language' => 'zh-hant',
				'source' => 'zh-cn',
			) ),
		);
	}

}
