<?php

namespace Wikibase\Client\Tests\Scribunto;

use Language;
use Parser;
use ParserOptions;
use Scribunto;
use Scribunto_LuaWikibaseLibrary;
use Title;
use Wikibase\Client\WikibaseClient;

/**
 * @covers Scribunto_LuaWikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseLibraryTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'LuaWikibaseLibraryTests' => __DIR__ . '/LuaWikibaseLibraryTests.lua',
		);
	}

	public function testConstructor() {
		$engine = Scribunto::newDefaultEngine( array() );
		$luaWikibaseLibrary = new Scribunto_LuaWikibaseLibrary( $engine );
		$this->assertInstanceOf( 'Scribunto_LuaWikibaseLibrary', $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertInternalType( 'array', $package );
		$this->assertArrayHasKey( 'setupInterface', $package );
		$this->assertInstanceOf(
			'Scribunto_LuaStandaloneInterpreterFunction',
			$package['setupInterface']
		);
	}

	public function testGetEntity() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entity = $luaWikibaseLibrary->getEntity( 'Q888', false );
		$this->assertEquals( array( null ), $entity );
	}

	public function testGetEntity_hasLanguageFallback() {
		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'ku-arab' )
		) );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityArray = $luaWikibaseLibrary->getEntity( 'Q885588', false );

		$expected = array(
			array(
				'id' => 'Q885588',
				'type' => 'item',
				'labels' => array(
					'ku-latn' => array(
						'language' => 'ku-latn',
						'value' => 'Pisîk'
					),
					'ku-arab' => array (
						'language' => 'ku-arab',
						'value' => 'پسیک',
						'source-language' => 'ku-latn',
					)
				),
				'schemaVersion' => 2,
			)
		);

		$this->assertEquals( $expected, $entityArray, 'getEntity' );

		$label = $luaWikibaseLibrary->getLabel( 'Q885588' );
		$this->assertEquals( array( 'پسیک' ), $label, 'getLabel' );
	}

	public function testGetEntityInvalidIdType() {
		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( array(), false );
	}

	public function testGetEntityInvalidEntityId() {
		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( 'X888', false );
	}

	public function testGetEntityId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityId = $luaWikibaseLibrary->getEntityId( 'CanHazKitten123' );
		$this->assertEquals( array( null ), $entityId );
	}

	private function newScribuntoLuaWikibaseLibrary() {
		$title =  Title::newFromText( 'Whatever' );
		$parser = new Parser();
		$parser->startExternalParse( $title, new ParserOptions(), Parser::OT_HTML );

		$engine = Scribunto::newDefaultEngine( array(
			'parser' => $parser,
			'title' => $title
		) );
		$engine->load();

		return new Scribunto_LuaWikibaseLibrary( $engine );
	}

}
