<?php

namespace Wikibase\Client\Tests\Hooks;

use File;
use PHPUnit4And6Compat;
use Title;

use Wikibase\Client\Hooks\SkinAfterBottomScriptsHandler;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;

/**
 * @covers \Wikibase\Client\Hooks\SkinAfterBottomScriptsHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SkinAfterBottomScriptsHandlerTest extends \PHPUnit\Framework\TestCase { // phpcs:ignore
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider createSchemaProvider
	 */
	public function testCreateSchema( $revisionTimestamp, $image, $description, $expected ) {
		$repoLinker = new RepoLinker( 'https://www.wikidata.org', '/wiki/$1', '/w' );
		$handler = new SkinAfterBottomScriptsHandler( $repoLinker );

		$client = WikibaseClient::getDefaultInstance();
		$title = $this->mockTitle( 'https://de.wikipedia.org/wiki', 'Douglas Adams', '1022523983' );
		$actual = $handler->createSchema(
			$client, $title, $revisionTimestamp, 'https://www.wikidata.org/wiki/Q42', $image, $description
		);
		$this->assertEquals( $expected, $actual, 'schema' );
	}

	public function createSchemaProvider() {
		$nullExpected = [
			"@context" => "https://schema.org",
			"@type" => "Article",
			"name" => "Douglas Adams",
			"url" => "https://de.wikipedia.org/wiki/Douglas_Adams",
			"sameAs" => "https://www.wikidata.org/wiki/Q42",
			"mainEntity" => "https://www.wikidata.org/wiki/Q42",
			"author" => [ "@type" => "Organization", "name" => "Wikipedia" ],
			"publisher" => [
				"@type" => "Organization",
				"name" => "Wikimedia Foundation, Inc.",
				"logo" => [
					"@type" => "ImageObject",
					"url" => "https://www.wikidata.org/extensions/Wikibase/client/assets/wikimedia.png"
				]
			],
			"datePublished" => "2002-05-27T18:26:23Z"
		];

		$image = $this->mockFile(
			'https://upload.wikimedia.org/wikipedia/commons/c/c0/Douglas_adams_portrait_cropped.jpg'
		);
		$nonNullExpected = [
			"@context" => "https://schema.org",
			"@type" => "Article",
			"name" => "Douglas Adams",
			"url" => "https://de.wikipedia.org/wiki/Douglas_Adams",
			"sameAs" => "https://www.wikidata.org/wiki/Q42",
			"mainEntity" => "https://www.wikidata.org/wiki/Q42",
			"author" => [ "@type" => "Organization", "name" => "Wikipedia" ],
			"publisher" => [
				"@type" => "Organization",
				"name" => "Wikimedia Foundation, Inc.",
				"logo" => [
					"@type" => "ImageObject",
					"url" => "https://www.wikidata.org/extensions/Wikibase/client/assets/wikimedia.png"
				]
			],
			"datePublished" => "2002-05-27T18:26:23Z",
			"dateModified" => "2018-09-28T20:16:12Z",
			"image" => "https://upload.wikimedia.org/wikipedia/commons/c/c0/Douglas_adams_portrait_cropped.jpg",
			"headline" => "British author and humorist (1952–2001)"
		];

		return [
			[ null, null, null, $nullExpected ],
			[ '1538165772', $image, 'British author and humorist (1952–2001)', $nonNullExpected ]
		];
	}

	/**
	 * @param string|null $url
	 * @return File
	 */
	private function mockFile( $url = null ) {
		$mock = $this->getMockForAbstractClass(
			File::class, [ false, false ], '', true, true, true, [ 'getUrl' ]
		);
		$mock->expects( $this->any() )
			->method( 'getUrl' )
			->will( $this->returnValue( $url ) );
		return $mock;
	}

	/**
	 * @param string $baseURL
	 * @param string $text
	 * @param string|null $earliestRevTimestamp
	 * @return Title
	 */
	private function mockTitle( $baseURL, $titleText, $earliestRevTimestamp = null ) {
		$mock = $this->getMock( Title::class );
		$mock->expects( $this->any() )
			->method( 'getFullURL' )
			->will( $this->returnValue( $baseURL . '/' . str_replace( ' ', '_', $titleText ) ) );
		$mock->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $titleText ) );
		$mock->expects( $this->any() )
			->method( 'getEarliestRevTime' )
			->will( $this->returnValue( $earliestRevTimestamp ) );
		return $mock;
	}

}
