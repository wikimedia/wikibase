<?php

namespace Wikibase\Test;

use \Wikibase\Item;
use \Wikibase\ItemContent;

/**
 * Tests for the SpecialItemByTitle class.
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
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Database
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SpecialEntityDataTest extends SpecialPageTestBase {

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

	protected function newSpecialPage() {
		$page = new \SpecialEntityData();
		$page->getContext()->setOutput( new \OutputPage( $page->getContext() ) );
		return $page;
	}

	public static function provideExecute() {
		$cases = array();

		$cases[] = array( // #0: no params, show form
			'',      // subpage
			array(), // parameters
			'!<p>!', // output regex //TODO: be more specific
			200,       // http code
		);

		$cases[] = array( // #1: valid item ID
			'',      // subpage
			array( 'id' => '{testitemid}' ), // parameters
			'!^\{.*Raarrr!', // output regex
			200,       // http code
		);

		$cases[] = array( // #2: invalid item ID
			'',      // subpage
			array( 'id' => 'q1231231230' ), // parameters
			'!!', // output regex
			404,  // http code
		);

		$cases[] = array( // #3: revision ID
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'revision' => '{testitemrev}',
			),
			'!^\{.*Raarr!', // output regex
			200,       // http code
		);

		$cases[] = array( // #4: bad revision ID
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'revision' => '1231231230',
			),
			'!!', // output regex
			404,       // http code
		);

		$cases[] = array( // #5: alternative format
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'php',
			),
			'!^a:\d+.*Raarr!', // output regex
			200,       // http code
		);

		$cases[] = array( // #6: mime type
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'application/json',
			),
			'!^\{.*Raarr!', // output regex
			200,       // http code
		);

		$cases[] = array( // #7: bad format
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'sdakljflsd',
			),
			'!!', // output regex
			415,  // http code
		);

		$cases[] = array( // #8: xml
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'xml',
			),
			'!<entity!', // output regex
			200,       // http code
		);

		$cases[] = array( // #9: evil stuff
			'',      // subpage
			array( // parameters
				'id' => '////',
				'revision' => '::::',
				'format' => '...',
			),
			'!!', // output regex
			404,  // http code
		);

		$subpageCases = array();

		foreach ( $cases as $c ) {
			$case = $c;
			$case[0] = '';

			if ( isset( $case[1]['id'] ) ) {
				$case[0] .= $case[1]['id'];
				unset( $case[1]['id'] );
			}

			if ( isset( $case[1]['revision'] ) ) {
				$case[0] .= ':' . $case[1]['revision'];
				unset( $case[1]['revision'] );
			}

			if ( isset( $case[1]['format'] ) ) {
				$case[0] .= '.' . $case[1]['format'];
				unset( $case[1]['format'] );
			}

			$subpageCases[] = $case;
		}

		$cases = array_merge( $cases, $subpageCases );

		return $cases;
	}

	protected static function injectIds( &$data, \Wikibase\Entity $entity ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $k => &$v ) {
				self::injectIds( $v, $entity );
			}
		} else if ( is_string( $data ) ) {
			$data = str_replace( '{testitemid}', $entity->getId()->getPrefixedId(), $data );

			if ( strpos( $data, '{testitemrev}' ) >= 0 ) {
				$content = \Wikibase\EntityContentFactory::singleton()->getFromId( $entity->getId() );
				$data = str_replace( '{testitemrev}', $content->getWikiPage()->getLatest(), $data );
			}
		}
	}

	/**
	 * @dataProvider provideExecute
	 *
	 * @param string $subpage The subpage to request (or '')
	 * @param array  $params  Request parameters
	 * @param string $expRegExp   Regex to match the output against.
	 * @param int    $expCode     Expected HTTP status code
	 * @param array  $expHeaders  Expected HTTP response headers
	 */
	public function testExecute( $subpage, $params, $expRegExp, $expCode = 200, $expHeaders = array() ) {
		$item = $this->getTestItem();

		self::injectIds( $subpage, $item );
		self::injectIds( $params, $item );

		$request = new \FauxRequest( $params );
		$request->response()->header( 'Status: 200 OK', true, 200 ); // init/reset

		try {
			/* @var \FauxResponse $response */
			list( $output, $response ) = $this->executeSpecialPage( $subpage, $request );

			$this->assertEquals( $expCode, $response->getStatusCode(), "status code" );
			$this->assertRegExp( $expRegExp, $output, "outpout" );

			foreach ( $expHeaders as $name => $expected ) {
				$this->assertEquals( $expected, $response->getheader( $name ), "header: $name" );
			}
		} catch ( \HttpError $e ) {
			//XXX: wait for HttpError::getStatusCode
			//$this->assertEquals( $expCode, $e->getStatusCode(), "status code" );

			//XXX: this is a horrible workaround for the above:
			ob_start();
			$e->report();
			$html = ob_get_contents();
			ob_end_clean();

			//$this->assertRegExp( '/' . $expCode . '/', $html ); //XXX: oh god, kill me!

			//XXX: this doesn't work.
			//$html = $e->getHTML();

			$this->assertRegExp( $expRegExp, $html, "error output" );
		}
	}

}
