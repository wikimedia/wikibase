<?php

namespace Wikibase\Test;

use DataTypes\DataTypeFactory;
use Title;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\LinkedData\EntityDataSerializationService;
use Wikibase\LinkedData\EntityDataRequestHandler;
use Wikibase\LinkedData\EntityDataUriManager;
use Wikibase\Property;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\LinkedData\EntityDataRequestHandler
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
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseEntityData
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityDataRequestHandlerTest extends \MediaWikiTestCase {

	/**
	 * @var \Title
	 */
	protected $interfaceTitle;

	protected $obLevel;

	public function setUp() {
		parent::setUp();

		$this->interfaceTitle = Title::newFromText( "Special:EntityDataRequestHandlerTest" );

		$this->obLevel = ob_get_level();
	}

	public function tearDown() {
		$obLevel = ob_get_level();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}

		if ( $obLevel !== $this->obLevel ) {
			$this->fail( "Test changed output buffer level: was {$this->obLevel} before test, but $obLevel after test.");
		}

		parent::tearDown();
	}

	protected function saveItem( Item $item ) {
		$content = ItemContent::newFromItem( $item );
		$content->save( "testing", null, EDIT_NEW );
	}

	public function getTestItem() {
		static $item;

		if ( $item === null ) {
			$item = Item::newEmpty();
			$item->setLabel( 'en', 'Raarrr' );
			$this->saveItem( $item );
		}

		return $item;
	}

	/**
	 * @return EntityDataRequestHandler
	 */
	protected function newHandler() {
		$entityLookup = new MockRepository();
		$dataTypeFactory = new DataTypeFactory( EntityDataSerializationServiceTest::$dataTypes );

		$idFormatter = new EntityIdFormatter( new FormatterOptions() );
		$idParser = new EntityIdParser( new ParserOptions() );

		$serializerFactory = new SerializerFactory( new SerializationOptions() );

		$contentFactory = new EntityContentFactory(
			$idFormatter,
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				CONTENT_MODEL_WIKIBASE_PROPERTY
			)
		);

		$service = new EntityDataSerializationService(
			EntityDataSerializationServiceTest::URI_BASE,
			EntityDataSerializationServiceTest::URI_DATA,
			$entityLookup,
			$dataTypeFactory,
			$idFormatter,
			$serializerFactory
		);

		$service->setFormatWhiteList(
			array(
				// using the API
				'json', // default
				'php',
				'xml',

				// using easyRdf
				'rdfxml',
				'n3',
				'turtle',
				'ntriples',
			)
		);

		$extensions = array(
			// using the API
			'json' => 'json', // default
			'php' => 'php',
			'xml' => 'xml',

			// using easyRdf
			'rdfxml' => 'rdf',
			'n3' => 'n3',
			'turtle' => 'ttl',
			'ntriples' => 'n3',
		);

		$uriManager = new EntityDataUriManager(
			$this->interfaceTitle,
			$extensions,
			$idFormatter,
			$contentFactory
		);

		$handler = new EntityDataRequestHandler(
			$uriManager,
			$contentFactory,
			$idParser,
			$idFormatter,
			$service,
			'json',
			1800,
			false,
			null
		);
		return $handler;
	}

	/**
	 * Substitutes placeholders using the concrete values from the given entity.
	 * Known placeholders are:
	 *
	 *  {testitemid}, {lowertestitemid}, {testitemrev}, {testitemtimestamp}
	 *
	 * @param mixed $data The data in which to substitude placeholders.
	 *        If this is an erray, injectIds is called on all elements recursively.
	 * @param Entity $entity
	 *
	 * @todo: use EntityRevision once we have that
	 */
	public static function injectIds( &$data, Entity $entity ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $k => &$v ) {
				self::injectIds( $v, $entity );
			}
		} else if ( is_string( $data ) ) {
			$data = str_replace( '{testitemid}', strtoupper( $entity->getId()->getPrefixedId() ), $data );
			$data = str_replace( '{lowertestitemid}', strtolower( $entity->getId()->getPrefixedId() ), $data );

			$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getFromId( $entity->getId() );
			$data = str_replace( '{testitemrev}', $content->getWikiPage()->getLatest(), $data );

			$ts = wfTimestamp( TS_RFC2822, $content->getWikiPage()->getTimestamp() );
			$data = str_replace( '{testitemtimestamp}', $ts, $data );
		}
	}

	/**
	 * @param $params
	 * @param $headers
	 *
	 * @return \OutputPage
	 */
	protected function makeOutputPage( $params, $headers ) {
		// construct request
		$request = new \FauxRequest( $params );
		$request->response()->header( 'Status: 200 OK', true, 200 ); // init/reset

		foreach ( $headers as $name => $value ) {
			$request->setHeader( strtoupper( $name ), $value );
		}

		// construct Context and OutputPage
		/* @var \FauxResponse $response */
		$response = $request->response();

		$context = new \DerivativeContext( \RequestContext::getMain() );
		$context->setRequest( $request );

		$output = new \OutputPage( $context );
		$output->setTitle( $this->interfaceTitle );
		$context->setOutput( $output );

		return $output;
	}

	public function handleRequestProvider() {
		return EntityDataTestProvider::provideHandleRequest();
	}

	/**
	 * @dataProvider handleRequestProvider
	 *
	 * @param string $subpage The subpage to request (or '')
	 * @param array  $params  Request parameters
	 * @param array  $headers  Request headers
	 * @param string $expRegExp   Regex to match the output against.
	 * @param int    $expCode     Expected HTTP status code
	 * @param array  $expHeaders  Expected HTTP response headers
	 */
	public function testHandleRequest( $subpage, $params, $headers, $expRegExp, $expCode = 200, $expHeaders = array() ) {
		$item = $this->getTestItem();

		// inject actual ID of test items
		self::injectIds( $subpage, $item );
		self::injectIds( $params, $item );
		self::injectIds( $headers, $item );
		self::injectIds( $expRegExp, $item );
		self::injectIds( $expHeaders, $item );

		$output = $this->makeOutputPage( $params, $headers );
		$request = $output->getRequest();
		$response = $request->response();

		// construct handler
		$handler = $this->newHandler();

		try {
			ob_start();
			$handler->handleRequest( $subpage, $request, $output );

			if ( $output->getRedirect() !== '' ) {
				// hack to apply redirect to web response
				$output->output();
			}

			$text = ob_get_contents();
			ob_end_clean();

			$this->assertEquals( $expCode, $response->getStatusCode(), "status code" );
			$this->assertRegExp( $expRegExp, $text, "output" );

			foreach ( $expHeaders as $name => $exp ) {
				$value = $response->getheader( $name );
				$this->assertNotNull( $value, "header: $name" );
				$this->assertType( 'string', $value, "header: $name" );
				$this->assertRegExp( $exp, $value, "header: $name" );
			}
		} catch ( \HttpError $e ) {
			ob_end_clean();
			$this->assertEquals( $expCode, $e->getStatusCode(), "status code" );
			$this->assertRegExp( $expRegExp, $e->getHTML(), "error output" );
		}
	}

	//TODO: test canHandleRequest
	//TODO: test httpContentNegotiation
	//TODO: test getCanonicalFormat
	//TODO: test ALL the things!
}
