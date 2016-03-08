<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\StringValue;
use MediaWikiTestCase;
use SpecialPage;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilderFactory;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\ExternalLinksDataUpdater;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdater;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityParserOutputGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityParserOutputGeneratorTest extends MediaWikiTestCase {

	public function testGetParserOutput() {
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator();

		$item = $this->newItem();
		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $item, 13044, $timestamp );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision );

		$this->assertSame( '<TITLE>', $parserOutput->getTitleText(), 'title text' );
		$this->assertSame( '<HTML>', $parserOutput->getText(), 'html text' );

		$this->assertSame(
			'<PLACEHOLDERS>',
			$parserOutput->getExtensionData( 'wikibase-view-chunks' ),
			'view chunks'
		);

		$this->assertSame( array( '<JS>' ), $parserOutput->getJsConfigVars(), 'config vars' );

		$this->assertEquals(
			'kitten item',
			$parserOutput->getExtensionData( 'wikibase-titletext' ),
			'title text'
		);

		$this->assertEquals(
			array( 'http://an.url.com', 'https://another.url.org' ),
			array_keys( $parserOutput->getExternalLinks() ),
			'external links'
		);

		$this->assertEquals(
			array( 'File:This_is_a_file.pdf', 'File:Selfie.jpg' ),
			array_keys( $parserOutput->getImages() ),
			'images'
		);

		// TODO would be nice to test this, but ReferencedEntitiesDataUpdater uses LinkBatch which uses the database
//		$this->assertEquals(
//			array( 'item:Q42', 'item:Q35' ),
//			array_keys( $parserOutput->getLinks()[NS_MAIN] ),
//			'badges'
//		);

		$this->assertArrayEquals(
			array(
				new ItemId( 'Q42' ),
				new ItemId( 'Q35' ),
				new PropertyId( 'P42' ),
				new PropertyId( 'P10' )
			),
			$parserOutput->getExtensionData( 'referenced-entities' )
		);

		$jsonHref = SpecialPage::getTitleFor( 'EntityData', $item->getId()->getSerialization() . '.json' )->getCanonicalURL();
		$ntHref = SpecialPage::getTitleFor( 'EntityData', $item->getId()->getSerialization() . '.nt' )->getCanonicalURL();

		$this->assertEquals(
			array(
				array(
					'rel' => 'alternate',
					'href' => $jsonHref,
					'type' => 'application/json'
				),
				array(
					'rel' => 'alternate',
					'href' => $ntHref,
					'type' => 'application/n-triples'
				)
			),
			$parserOutput->getExtensionData( 'wikibase-alternate-links' ),
			'alternate links (extension data)'
		);
	}

	public function testGetParserOutput_dontGenerateHtml() {
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator( false );

		$item = $this->newItem();
		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $item, 13044, $timestamp );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision, false );

		$this->assertSame( '', $parserOutput->getText() );
		// ParserOutput without HTML must not end up in the cache.
		$this->assertFalse( $parserOutput->isCacheable() );
	}

	public function testTitleText_ItemHasNolabel() {
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator();

		$item = new Item( new ItemId( 'Q7799929' ) );
		$item->setDescription( 'en', 'a kitten' );

		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $item, 13045, $timestamp );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision );

		$this->assertEquals(
			'Q7799929',
			$parserOutput->getExtensionData( 'wikibase-titletext' ),
			'title text'
		);
	}

	private function newEntityParserOutputGenerator( $createView = true ) {
		$entityDataFormatProvider = new EntityDataFormatProvider();

		$formats = array( 'json', 'ntriples' );
		$entityDataFormatProvider->setFormatWhiteList( $formats );

		$entityTitleLookup = $this->getEntityTitleLookupMock();

		$propertyDataTypeMatcher = new PropertyDataTypeMatcher( $this->getPropertyDataTypeLookup() );

		$dataUpdaters = array(
			new ExternalLinksDataUpdater( $propertyDataTypeMatcher ),
			new ImageLinksDataUpdater( $propertyDataTypeMatcher ),
			new ReferencedEntitiesDataUpdater(
				$entityTitleLookup,
				new BasicEntityIdParser()
			)
		);

		return new EntityParserOutputGenerator(
			$this->getEntityViewFactory( $createView ),
			$this->getConfigBuilderMock(),
			$entityTitleLookup,
			new SqlEntityInfoBuilderFactory(),
			$this->newLanguageFallbackChain(),
			TemplateFactory::getDefaultInstance(),
			$entityDataFormatProvider,
			$dataUpdaters,
			'en',
			true
		);
	}

	private function newLanguageFallbackChain() {
		$fallbackChain = $this->getMockBuilder( 'Wikibase\LanguageFallbackChain' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackChain->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function( $labels ) {
				if ( array_key_exists( 'en', $labels ) ) {
					return array(
						'value' => $labels['en'],
						'language' => 'en',
						'source' => 'en'
					);
				}

				return null;
			} ) );

		return $fallbackChain;
	}

	private function newItem() {
		$item = new Item( new ItemId( 'Q7799929' ) );

		$item->setLabel( 'en', 'kitten item' );

		$statements = $item->getStatements();

		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'http://an.url.com' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'https://another.url.org' ) ) );

		$statements->addNewStatement( new PropertyValueSnak( 10, new StringValue( 'File:This is a file.pdf' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 10, new StringValue( 'File:Selfie.jpg' ) ) );

		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'kitten', array( new ItemId( 'Q42' ) ) );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'meow', array( new ItemId( 'Q42' ), new ItemId( 'Q35' ) ) );

		return $item;
	}

	private function getEntityViewFactory( $createView ) {
		$entityViewFactory = $this->getMockBuilder( 'Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory' )
			->disableOriginalConstructor()
			->getMock();

		$entityViewFactory->expects( $createView ? $this->once() : $this->never() )
			->method( 'newEntityView' )
			->will( $this->returnValue( $this->getEntityView() ) );

		return $entityViewFactory;
	}

	private function getEntityView() {
		$entityView = $this->getMockBuilder( 'Wikibase\View\EntityView' )
			->setMethods( array(
				'getTitleHtml',
				'getHtml',
				'getPlaceholders',
			) )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$entityView->expects( $this->any() )
			->method( 'getTitleHtml' )
			->will( $this->returnValue( '<TITLE>' ) );

		$entityView->expects( $this->any() )
			->method( 'getHtml' )
			->will( $this->returnValue( '<HTML>' ) );

		$entityView->expects( $this->any() )
			->method( 'getPlaceholders' )
			->will( $this->returnValue( '<PLACEHOLDERS>' ) );

		return $entityView;
	}

	private function getConfigBuilderMock() {
		$configBuilder = $this->getMockBuilder( 'Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$configBuilder->expects( $this->any() )
			->method( 'build' )
			->will( $this->returnValue( array( '<JS>' ) ) );

		return $configBuilder;
	}

	private function getEntityTitleLookupMock() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle(
					NS_MAIN,
					$id->getEntityType() . ':' . $id->getSerialization()
				);
			} ) );

		return $entityTitleLookup;
	}

	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P42' ), 'url' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P10' ), 'commonsMedia' );

		return $dataTypeLookup;
	}

}
