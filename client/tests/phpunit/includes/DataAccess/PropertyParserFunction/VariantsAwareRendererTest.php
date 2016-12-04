<?php

namespace Wikibase\Client\Tests\DataAccess\PropertyParserFunction;

use ParserOutput;
use Title;
use Wikibase\Client\DataAccess\PropertyParserFunction\LanguageAwareRenderer;
use Wikibase\Client\DataAccess\PropertyParserFunction\StatementGroupRendererFactory;
use Wikibase\Client\DataAccess\PropertyParserFunction\VariantsAwareRenderer;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\DataAccess\PropertyParserFunction\VariantsAwareRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class VariantsAwareRendererTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider renderProvider
	 */
	public function testRender( $expected, $itemId, $variants, $propertyLabel ) {
		$languageRenderer = $this->getLanguageAwareRenderer();

		$rendererFactory = $this->getMockBuilder( StatementGroupRendererFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$rendererFactory->expects( $this->any() )
			->method( 'newLanguageAwareRenderer' )
			->will( $this->returnValue( $languageRenderer ) );

		$rendererFactory->expects( $this->any() )
			->method( 'getLanguageAwareRendererFromCode' )
			->will( $this->returnValue( $languageRenderer ) );

		$languageRenderers = array();

		foreach ( $variants as $variant ) {
			$languageRenderers[$variant] = $languageRenderer;
		}

		$variantsRenderer = new VariantsAwareRenderer(
			$languageRenderers,
			$variants
		);

		$mockParser = $this->getMockBuilder( ParserOutput::class )
			->setMethods( [ 'addTrackingCategory' ] )
			->getMock();
		$mockTitle = $this->getMock( Title::class );

		$result = $variantsRenderer->render( $itemId, $propertyLabel, $mockParser, $mockTitle );

		$this->assertEquals( $expected, $result );
	}

	public function renderProvider() {
		$itemId = new ItemId( 'Q3' );

		return array(
			array(
				'-{zh:mooooo;zh-hans:mooooo;zh-hant:mooooo;zh-cn:mooooo;zh-hk:mooooo;}-',
				$itemId,
				array( 'zh', 'zh-hans', 'zh-hant', 'zh-cn', 'zh-hk' ),
				'cat'
			),
			// Don't create "-{}-" for empty input,
			// to keep the ability to check a missing property with {{#if: }}.
			array(
				'',
				$itemId,
				array(),
				'cat'
			)
		);
	}

	/**
	 * @return LanguageAwareRenderer
	 */
	private function getLanguageAwareRenderer() {
		$languageRenderer = $this->getMockBuilder( LanguageAwareRenderer::class )
		->disableOriginalConstructor()
		->getMock();

		$languageRenderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'mooooo' ) );

		return $languageRenderer;
	}

}
