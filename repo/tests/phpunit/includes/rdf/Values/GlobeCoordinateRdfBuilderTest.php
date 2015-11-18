<?php

namespace Wikibase\Test\Rdf;

use DataValues\Geo\Values\LatLongValue;
use DataValues\GlobeCoordinateValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Rdf\Values\GlobeCoordinateRdfBuilder;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\Rdf\Values\GlobeCoordinateRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class GlobeCoordinateRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	public function provideAddValue() {
		$value = new GlobeCoordinateValue(
			new LatLongValue( 12.25, -45.5 ),
			0.025,
			'https://www.wikidata.org/entity/Q2'
		);

		$snak = new PropertyValueSnak( new PropertyId( 'P7' ), $value );

		return array(
			'simple' => array(
				$snak,
				false,
				array(
					'<http://www/Q1> <http://acme/statement/P7> "Point(12.25 -45.5)"^^<http://acme/geo/wktLiteral> .',
				)
			),
			'complex' => array(
				$snak,
				true,
				array(
					'<http://www/Q1> '
						. '<http://acme/statement/P7> '
						. '"Point(12.25 -45.5)"^^<http://acme/geo/wktLiteral> .',
					'<http://www/Q1> '
						. '<http://acme/statement/value/P7> '
						. '<http://acme/value/7901049a90a3b6a6cbbae50dc76c2da9> .',
					'<http://acme/value/7901049a90a3b6a6cbbae50dc76c2da9> '
						. '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> '
						. '<http://acme/onto/GlobecoordinateValue> .',
					'<http://acme/value/7901049a90a3b6a6cbbae50dc76c2da9> '
						. '<http://acme/onto/geoLatitude> '
						. '"12.25"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/7901049a90a3b6a6cbbae50dc76c2da9> '
						. '<http://acme/onto/geoLongitude> '
						. '"-45.5"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/7901049a90a3b6a6cbbae50dc76c2da9> '
						. '<http://acme/onto/geoPrecision> '
						. '"0.025"^^<http://www.w3.org/2001/XMLSchema#decimal> .',
					'<http://acme/value/7901049a90a3b6a6cbbae50dc76c2da9> '
						. '<http://acme/onto/geoGlobe> '
						. '<https://www.wikidata.org/entity/Q2> .',
				)
			),
		);
	}

	/**
	 * @dataProvider provideAddValue
	 */
	public function testAddValue( PropertyValueSnak $snak, $complex, array $expected ) {
		$vocab = new RdfVocabulary( 'http://acme.com/item/', 'http://acme.com/data/' );

		$snakWriter = new NTriplesRdfWriter();
		$snakWriter->prefix( 'www', "http://www/" );
		$snakWriter->prefix( 'acme', "http://acme/" );
		$snakWriter->prefix( RdfVocabulary::NSP_CLAIM_VALUE, "http://acme/statement/value/" );
		$snakWriter->prefix( RdfVocabulary::NSP_CLAIM_STATEMENT, "http://acme/statement/" );
		$snakWriter->prefix( RdfVocabulary::NS_VALUE, "http://acme/value/" );
		$snakWriter->prefix( RdfVocabulary::NS_ONTOLOGY, "http://acme/onto/" );
		$snakWriter->prefix( RdfVocabulary::NS_GEO, "http://acme/geo/" );

		if ( $complex ) {
			$valueWriter = $snakWriter->sub();
			$helper = new ComplexValueRdfHelper( $vocab, $valueWriter, new HashDedupeBag() );
		} else {
			$helper = null;
		}

		$builder = new GlobeCoordinateRdfBuilder( $helper );

		$snakWriter->start();
		$snakWriter->about( 'www', 'Q1' );

		$builder->addValue(
			$snakWriter,
			RdfVocabulary::NSP_CLAIM_STATEMENT,
			$vocab->getEntityLName( $snak->getPropertyid() ),
			'DUMMY',
			$snak
		);

		$triples = rtrim( $snakWriter->drain(), "\n" );
		$this->assertEquals( join( "\n", $expected ), $triples );
	}

}
