<?php

namespace Wikibase\Test;

use ValueValidators\Result;
use WebRequest;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases;
use Wikibase\Repo\Specials\SpecialWikibaseRepoPage;
use Wikibase\Validators\TermValidatorFactory;
use Wikibase\Validators\UniquenessViolation;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
class SpecialSetLabelDescriptionAliasesTest extends SpecialWikibaseRepoPageTestBase {

	protected $languageCodes = array( 'en', 'de', 'de-ch', 'ii', 'zh' );


	/**
	 * @see SpecialPageTestBase::newSpecialPage()
	 *
	 * @return SpecialSetLabelDescriptionAliases
	 */
	protected function newSpecialPage() {
		$page = new SpecialSetLabelDescriptionAliases();

		$this->setMockServices( $page );

		return $page;
	}

	/**
	 * @param SpecialSetLabelDescriptionAliases $page
	 */
	protected function setMockServices( SpecialWikibaseRepoPage $page ) {
		$page->setServices(
			$this->getSummaryFormatter(),
			$this->getEntityRevisionLookup(),
			$this->getEntityTitleLookup(),
			$this->getEntityStore(),
			$this->getEntityPermissionChecker(),
			$this->getSiteStore(),
			$this->getFingerprintChangeOpsFactory(),
			$this->getContentLanguages()
		);
	}

	/**
	 * @return FingerprintChangeOpFactory
	 */
	private function getFingerprintChangeOpsFactory() {
		$maxLength = 32;
		
		return new FingerprintChangeOpFactory(
			new TermValidatorFactory(
				$maxLength,
				$this->languageCodes,
				$this->getIdParser(),
				$this->getLabelDescriptionDuplicateDetector(),
				$this->mockRepository
			)
		);
	}

	/**
	 * @return LabelDescriptionDuplicateDetector
	 */
	private function getLabelDescriptionDuplicateDetector() {
		$detector = $this->getMockBuilder( 'Wikibase\LabelDescriptionDuplicateDetector' )
			->disableOriginalConstructor()
			->getMock();

		$self = $this; // yay PHP 5.3
		$detector->expects( $this->any() )
			->method( 'detectTermConflicts' )
			->will( $this->returnCallback( function(
				$entityType,
				array $labels,
				array $descriptions = null,
				EntityId $ignoreEntityId = null
			) use ( $self ) {
				$errors = array();

				$errors = array_merge( $errors, $self->detectDupes( $labels ) );
				$errors = array_merge( $errors, $self->detectDupes( $descriptions ) );

				$result = empty( $errors ) ? Result::newSuccess() : Result::newError( $errors );
				return $result;
			} ) );

		return $detector;
	}

	/**
	 * Mock duplicate detection: the term "DUPE" is considered a duplicate.
	 *
	 * @param string[] $terms
	 *
	 * @return array
	 */
	public function detectDupes( $terms ) {
		$errors = array();

		foreach ( $terms as $languageCode => $term ) {
			if ( $term === 'DUPE' ) {
				$q666 = new ItemId( 'Q666' );

				$errors[] =  new UniquenessViolation(
					$q666,
					'found conflicting terms',
					'test-conflict',
					array(
						$term,
						$languageCode,
						$q666,
					)
				);
			}
		}

		return $errors;
	}

	/**
	 * @return ContentLanguages
	 */
	private function getContentLanguages() {
		$languages = $this->getMock( 'Wikibase\Lib\ContentLanguages' );

		$languages->expects( $this->any() )
			->method( 'getLanguages' )
			->will( $this->returnValue( $this->languageCodes ) );

		$languageCodes = $this->languageCodes; // for PHP 5.3
		$languages->expects( $this->any() )
			->method( 'hasLanguage' )
			->will( $this->returnCallback( function( $code ) use ( $languageCodes ) {
				return in_array( $code, $languageCodes );
			} ) );

		return $languages;
	}

	/**
	 * @param string[] $labels
	 * @param string[] $descriptions
	 * @param string[][] $aliases
	 *
	 * @return Fingerprint
	 */
	private function makeFingerprint( array $labels, array $descriptions, array $aliases ) {
		$fingerprint = new Fingerprint();

		foreach ( $labels as $lang => $text ) {
			$fingerprint->setLabel( $lang, $text );
		}

		foreach ( $descriptions as $lang => $text ) {
			$fingerprint->setDescription( $lang, $text );
		}

		foreach ( $aliases as $lang => $texts ) {
			$fingerprint->setAliasGroup( $lang, $texts );
		}

		return $fingerprint;
	}

	public function provideExecute() {
		$formMatchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
				'name' => 'id',
			),
		);
		$formMatchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-language',
				'class' => 'wb-input',
				'name' => 'language',
				'value' => 'en',
			),
		);
		$formMatchers['label'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-label',
				'class' => 'wb-input',
				'name' => 'label',
			),
		);
		$formMatchers['description'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-description',
				'class' => 'wb-input',
				'name' => 'description',
			),
		);
		$formMatchers['aliases'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-aliases',
				'class' => 'wb-input',
				'name' => 'aliases',
			),
		);
		$formMatchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setlabeldescriptionaliases-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setlabeldescriptionaliases-submit',
			),
		);

		$withIdMatchers = $formMatchers;
		$withIdMatchers['id']['attributes'] = array(
			'type' => 'hidden',
			'name' => 'id',
			'value' => 'regexp:/Q\d+/',
		);
		$withIdMatchers['language']['attributes'] = array(
			'type' => 'hidden',
			'name' => 'language',
			'value' => 'en',
		);

		$withLanguageMatchers = $withIdMatchers;
		$withLanguageMatchers['language']['attributes']['value'] = 'de';
		$withLanguageMatchers['label']['attributes']['value'] = 'foo';

		$fooFingerprint = $this->makeFingerprint(
			array( 'de' => 'foo' ),
			array(),
			array()
		);

		return array(
			'no input' => array(
				$fooFingerprint,
				'',
				null,
				$formMatchers,
				null
			),

			'with id but no language' => array(
				$fooFingerprint,
				'$id',
				null,
				$withIdMatchers,
				null
			),

			'with id and language' => array(
				$fooFingerprint,
				'$id/de',
				null,
				$withLanguageMatchers,
				null
			),

			'with id and language attribute' => array(
				$fooFingerprint,
				'$id',
				new \FauxRequest( array( 'language' => 'de' ) ),
				$withLanguageMatchers,
				null
			),

			'add label' => array(
				$fooFingerprint,
				'$id',
				new \FauxRequest( array( 'language' => 'en', 'label' => 'FOO' ), true ),
				array(),
				$this->makeFingerprint(
					array( 'de' => 'foo', 'en' => 'FOO' ),
					array(),
					array()
				),
			),

			'replace label' => array(
				$fooFingerprint,
				'$id',
				new \FauxRequest( array( 'language' => 'de', 'label' => 'FOO' ), true ),
				array(),
				$this->makeFingerprint(
					array( 'de' => 'FOO' ),
					array(),
					array()
				),
			),

			'add description, keep label' => array(
				$fooFingerprint,
				'$id',
				new \FauxRequest( array( 'language' => 'de', 'description' => 'Lorem Ipsum' ), true ),
				array(),
				$this->makeFingerprint(
					array( 'de' => 'foo' ),
					array( 'de' => 'Lorem Ipsum' ),
					array()
				),
			),

			'set aliases' => array(
				$fooFingerprint,
				'$id',
				new \FauxRequest( array( 'language' => 'de', 'aliases' => 'foo|bar' ), true ),
				array(),
				$this->makeFingerprint(
					array( 'de' => 'foo' ),
					array(),
					array( 'de' => array( 'foo', 'bar' ) )
				),
			),

		);
	}

	/**
	 * @dataProvider provideExecute
	 */
	public function testExecute( Fingerprint $inputFingerprint, $subpage, WebRequest $request = null, array $tagMatchers, Fingerprint $expectedFingerprint = null ) {
		$inputEntity = new Item();
		$inputEntity->setFingerprint( $inputFingerprint );

		$this->mockRepository->putEntity( $inputEntity );
		$id = $inputEntity->getId();

		$this->newSpecialPage();

		$subpage = str_replace( '$id', $id->getSerialization(), $subpage );
		list( $output, $response ) = $this->executeSpecialPage( $subpage, $request );

		$redirect = ( $response instanceof \FauxResponse ) ? $response->getHeader( 'Location' ) : null;

		foreach( $tagMatchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to assert output: $key" );
		}

		if ( $expectedFingerprint ) {
			//TODO: look for an error message in $output
			$this->assertNotEmpty( $redirect, 'expected redirect after successful edit' );

			$actualEntity = $this->mockRepository->getEntity( $id );
			$actualFingerprint = $actualEntity->getFingerprint();

			$this->assetFingerprintEquals( $expectedFingerprint, $actualFingerprint );
		}
	}

	private function assetFingerprintEquals( Fingerprint $expected, Fingerprint $actual, $message = 'Fingerprint mismatches' ) {
		//TODO: compare serializations
		$this->assertTrue( $expected->equals( $actual ), $message );

	}

}
