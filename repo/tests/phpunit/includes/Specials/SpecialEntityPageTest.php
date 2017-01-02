<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use FauxResponse;
use HttpError;
use Interwiki;
use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\MediaWikiServices;
use SpecialPageTestBase;
use Title;
use WebRequest;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\Specials\SpecialEntityPage;

/**
 * @covers Wikibase\Repo\Specials\SpecialEntityPage
 *
 * @license GPL-2.0+
 */
class SpecialEntityPageTest extends SpecialPageTestBase {

	protected function setUp() {
		parent::setUp();

		$this->setUserLang( 'en' );
	}

	protected function newSpecialPage() {
		$page = new SpecialEntityPage( new BasicEntityIdParser() );

		return $page;
	}

	public function provideLocalEntityIdArgumentsToSpecialPage() {
		$url = Title::makeTitle( NS_SPECIAL, 'EntityData/Q100' )->getFullURL();
		return [
			'id as a sub page' => [ 'Q100', [], $url ],
			'id as a request parameter' => [ null, [ 'id' => 'Q100' ], $url ],
		];
	}

	/**
	 * @dataProvider provideLocalEntityIdArgumentsToSpecialPage
	 */
	public function testGivenLocalEntityId_executeRedirectsToSpecialEntityData(
		$subPage,
		array $requestParams,
		$expectedRedirectUrl
	) {
		$request = new FauxRequest( $requestParams );

		/* @var FauxResponse $response */
		list( , $response ) = $this->executeSpecialPage( $subPage, $request );

		$this->assertSame( 303, $response->getStatusCode() );
		$this->assertSame( $expectedRedirectUrl, $response->getHeader( 'Location' ) );
	}

	public function provideForeignEntityIdArgumentsToSpecialPage() {
		$url = 'https://foo.wiki/Special:EntityPage/Q100';
		return [
			'id as a sub page' => [ 'foo:Q100', [], $url ],
			'id as a request parameter' => [ null, [ 'id' => 'foo:Q100' ], $url ],
		];
	}

	/**
	 * @dataProvider provideForeignEntityIdArgumentsToSpecialPage
	 */
	public function testGivenForeignEntityId_executeRedirectsToOtherReposSpecialEntityPage(
		$subPage,
		array $requestParams,
		$expectedRedirectUrl
	) {
		$request = new FauxRequest( $requestParams );

		$services = MediaWikiServices::getInstance();
		$services->resetServiceForTesting( 'InterwikiLookup' );
		$services->redefineService(
			'InterwikiLookup',
			function() {
				$lookup = $this->getMock( InterwikiLookup::class );
				$lookup->expects( $this->any() )
					->method( 'fetch' )
					->will(
						$this->returnValue( new Interwiki( 'foo', 'https://foo.wiki/$1' ) )
					);
				return $lookup;
			}
		);

		/* @var FauxResponse $response */
		list( , $response ) = $this->executeSpecialPage( $subPage, $request );

		$this->assertSame( 303, $response->getStatusCode() );
		$this->assertSame( $expectedRedirectUrl, $response->getHeader( 'Location' ) );

		$services->resetServiceForTesting( 'InterwikiLookup' );
	}

	public function provideInvalidEntityIdArgumentsToSpecialPage() {
		return [
			'id as a sub page' => [ 'ABCDEF', [], 'ABCDEF' ],
			'id as a request parameter' => [ null, [ 'id' => 'ABCDEF' ], 'ABCDEF' ],
		];
	}

	/**
	 * @dataProvider provideInvalidEntityIdArgumentsToSpecialPage
	 */
	public function testGivenInvalidId_executeThrowException( $subPage, array $requestParams, $idExpectedInErrorMsg ) {
		$request = new FauxRequest( $requestParams );
		$this->setExpectedException( HttpError::class );

		try {
			$this->executeSpecialPage( $subPage, $request );
		} catch ( HttpError $exception ) {
			$this->assertSame( 400, $exception->getStatusCode() );
			$this->assertSame( wfMessage( 'wikibase-entitypage-bad-id', $idExpectedInErrorMsg )->text(), $exception->getMessage() );
			throw $exception;
		}
	}

	public function provideNoEntityIdArgumentsToSpecialPage() {
		return [
			'no sub page' => [ '' ],
			'empty id as a request parameter' => [ null, [ 'id' => '' ] ],
		];
	}

	/**
	 * @dataProvider provideNoEntityIdArgumentsToSpecialPage
	 */
	public function testGivenNoEntityId_executeShowsHelpMessage( $subPage, array $requestParams = [] ) {
		$request = new FauxRequest( $requestParams );

		/* @var FauxResponse $response */
		list( $output, ) = $this->executeSpecialPage( $subPage, $request );

		$this->assertContains( wfMessage( 'wikibase-entitypage-text' )->text(), $output );
	}

}
