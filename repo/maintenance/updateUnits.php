<?php
namespace Wikibase;

use DataValues\DecimalMath;
use DataValues\DecimalValue;
use Maintenance;
use Wikibase\Repo\Maintenance\SPARQLClient;
use Wikibase\Repo\WikibaseRepo;

$basePath =
	getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';
require_once __DIR__ . '/SPARQLClient.php';

/**
 * Update the conversion table for units.
 * Base unit types for Wikidata:
 * Q223662,Q208469
 * SI base unit,SI derived unit
 * TODO: add support to non-SI units
 * @package Wikibase
 */
class UpdateUnits extends Maintenance {


	/** Base URI
	 * @var string
	 */
	private $baseUri;
	/**
	 * Length of the base URI.
	 * Helper variable to speed up cutting it out.
	 * @var int
	 */
	private $baseLen;
	/**
	 * @var SPARQLClient
	 */
	private $client;

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Update unit conversion table." );

		$this->addOption( 'base-unit-types', 'Types of base units.', true, true );
		$this->addOption( 'base-uri', 'Base URI for the data.', false, true );
		$this->addOption( 'unit-class', 'Class for units.', false, true );
		$this->addOption( 'format', 'Output format, default is json.', false, true );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->error( "You need to have Wikibase enabled in order to use this maintenance script!",
				1 );
		}
		$format = $this->getOption( 'format', 'json' );
		if ( !is_callable( [ $this, 'format' . $format ] ) ) {
			$this->error( "Invalid format", 1 );
		}

		$repo = WikibaseRepo::getDefaultInstance();
		$endPoint = $repo->getSettings()->getSetting( 'sparqlEndpoint' );
		if ( !$endPoint ) {
			$this->error( 'SPARQL endpoint not defined', 1 );
		}
		$this->baseUri =
			$this->getOption( 'base-uri', $repo->getSettings()->getSetting( 'conceptBaseUri' ) );
		$this->client = new SPARQLClient( $endPoint, $this->baseUri );
		$this->baseLen = strlen( $this->baseUri );

		$unitClass = $this->getOption( 'unit-class' );
		if ( $unitClass ) {
			$filter = "FILTER EXISTS { ?unit wdt:P31/wdt:P279* wd:$unitClass }\n";
		} else {
			$filter = '';
		}

		// Get units usage stats. We don't care about units
		// That have been used less than 10 times, for now
		$unitUsage = $this->getUnitUsage( 10 );
		$baseUnits = $this->getBaseUnits( $filter );

		$convertUnits = [];
		$reconvert = [];

		foreach ( $this->getConvertableUnits( $filter ) as $unit ) {
			$converted =
				$this->convertUnit( $unit, $convertUnits, $baseUnits, $unitUsage, $reconvert );
			if ( $converted ) {
				$convertUnits[$unit['unit']] = $converted;
			}
		}

		// try to convert some units that reduce to other units
		while ( $reconvert ) {
			$converted = false;
			foreach ( $reconvert as $name => $unit ) {
				$convertedUnit = $this->convertDerivedUnit( $unit, $convertUnits );
				if ( $convertedUnit ) {
					$convertUnits[$name] = $convertedUnit;
					unset( $reconvert[$name] );
					$converted = true;
				}
			}
			// we didn't convert any on this step, no use to continue
			if ( !$converted ) {
				break;
			}
		}

		if ( $reconvert ) {
			// still have unconverted units
			foreach ( $reconvert as $name => $unit ) {
				$this->error( "Weird base unit: {$unit['unit']} reduces to {$unit['siUnit']} which is not base!" );
			}
		}

		// Add base units
		foreach ( $baseUnits as $base => $baseData ) {
			$convertUnits[$base] = [
				'factor' => "1",
				'unit' => $base,
				'label' => $baseData['unitLabel'],
				'siLabel' => $baseData['unitLabel']
			];
		}

		$formatter = 'format' . $format;
		echo $this->$formatter( $convertUnits );
	}

	/**
	 * Convert unit that does not reduce to a basic unit.
	 * @param string  $unit
	 * @param array[] $convertUnits List of units already converted
	 * @return string[]|null Converted data for the unit or null if no conversion possible.
	 */
	public function convertDerivedUnit( $unit, $convertUnits ) {
		if ( isset( $convertUnits[$unit['siUnit']] ) ) {
			// we have conversion now
			$name = $unit['unit'];
			$math = new DecimalMath();
			$newUnit = $convertUnits[$unit['siUnit']];
			$newFactor =
				$math->product( $this->makeDecimalValue( $unit['si'] ),
					$this->makeDecimalValue( $newUnit['factor'] ) );
			return [
				'factor' => trim( $newFactor->getValue(), '+' ),
				'unit' => $newUnit['unit'],
				'label' => $unit['unitLabel'],
				'siLabel' => $newUnit['siLabel']
			];
		}
		return null;
	}

	/**
	 * Create conversion data for a single unit.
	 * @param string[] $unit Unit data
	 * @param string[] $convertUnits Already converted data
	 * @param string[] $baseUnits Base unit list
	 * @param string[] $unitUsage Unit usage data
	 * @param string[] $reconvert Collection of units for re-conversion
	 * @return null|\string[] Produces conversion data for the unit or null if not possible.
	 */
	public function convertUnit( $unit, $convertUnits, $baseUnits, $unitUsage, &$reconvert ) {
		$unit['unit'] = substr( $unit['unit'], $this->baseLen );
		$unit['siUnit'] = substr( $unit['siUnit'], $this->baseLen );

		if ( $unit['unitLabel'][0] == 'Q' ) {
			// Skip exotic units that have no English name for now.
			// TODO: drop this
			return null;
		}

		if ( isset( $convertUnits[$unit['unit']] ) ) {
			// done already
			return null;
		}
		if ( $unit['unit'] == $unit['siUnit'] ) {
			// base unit
			if ( $unit['si'] != 1 ) {
				$this->error( "Weird unit: {$unit['unit']} is {$unit['si']} of itself!" );
				return null;
			}
			if ( !isset( $baseUnits[$unit['siUnit']] ) ) {
				$this->error( "Weird unit: {$unit['unit']} is self-referring but not base!" );
				return null;
			}
		}

		if ( !isset( $baseUnits[$unit['unit']] ) && !isset( $unitUsage[$unit['unit']] ) ) {
			$this->error( "Low usage unit {$unit['unit']}, skipping..." );
			return null;
		}

		if ( !isset( $baseUnits[$unit['siUnit']] ) ) {
			// target unit is not actually base
			$reconvert[$unit['unit']] = $unit;
		} else {
			return [
				'factor' => $unit['si'],
				'unit' => $unit['siUnit'],
				// These two are just for humans, not used by actual converter
				'label' => $unit['unitLabel'],
				'siLabel' => $unit['siUnitLabel']
			];
		}

		return null;
	}

	/**
	 * Format units as JSON
	 * @param $convertUnits
	 * @return string
	 */
	private function formatJSON( $convertUnits ) {
		return json_encode( $convertUnits, JSON_PRETTY_PRINT );
	}

	/**
	 * Get units that have usage more than bound.
	 * We don't care about units that have been used less than 10 times, for now.
	 * @param int $min Minimal usage for the unit.
	 * @return \string[]
	 */
	private function getUnitUsage( $min ) {
		$usageQuery = <<<UQUERY
SELECT ?unit (COUNT(DISTINCT ?v) as ?c) WHERE {
  ?v wikibase:quantityUnit ?unit .
  ?s ?p ?v .
  FILTER(?unit != wd:Q199)
# Exclude currencies
  FILTER NOT EXISTS { ?unit wdt:P31+ wd:Q8142 }
} GROUP BY ?unit
  HAVING(?c >= $min)
  ORDER BY DESC(?c)
  LIMIT 200
UQUERY;
		$unitUsage = $this->client->getIDs( $usageQuery, 'unit' );
		$unitUsage = array_flip( $unitUsage );
		return $unitUsage;
	}

	/**
	 * Get base units
	 * @param string $filter Unit filter
	 * @return array
	 */
	private function getBaseUnits( $filter ) {
		$types =
			str_replace( [ ',', 'Q' ], [ ' ', 'wd:Q' ], $this->getOption( 'base-unit-types' ) );

		$baseQuery = <<<QUERY
SELECT ?unit ?unitLabel WHERE {
  VALUES ?class {  $types }
  ?unit wdt:P31 ?class .
  $filter 
  SERVICE wikibase:label {
 bd:serviceParam wikibase:language "en" .
}      
}
QUERY;
		$baseUnitsData = $this->client->query( $baseQuery );
		$baseUnits = [];
		// arrange better lookup
		foreach ( $baseUnitsData as $base ) {
			$item = substr( $base['unit'], strlen( $this->baseUri ) );
			$baseUnits[$item] = $base;
		}
		return $baseUnits;
	}

	/**
	 * Retrieve the list of convertable units.
	 * @param $filter
	 * @return array[]|false List of units that can be converted
	 */
	private function getConvertableUnits($filter) {
		$unitsQuery = <<<QUERY
SELECT REDUCED ?unit ?si ?siUnit ?unitLabel ?siUnitLabel WHERE {
  ?unit wdt:P31 ?type .
  ?type wdt:P279* wd:Q47574 .
  # Not a currency
  FILTER (?type != wd:Q8142)
  # Not a cardinal number
  FILTER NOT EXISTS { ?unit wdt:P31 wd:Q163875 }
  $filter
  # Has conversion to SI Units
  ?unit p:P2370/psv:P2370 [ wikibase:quantityAmount ?si; wikibase:quantityUnit ?siUnit ] .
  SERVICE wikibase:label {
    bd:serviceParam wikibase:language "en" .
  }
# Enable this to select only units that are actually used
  FILTER EXISTS { [] wikibase:quantityUnit ?unit }
}        
QUERY;
		return $this->client->query( $unitsQuery );
	}

	/**
	 * Format units as CSV
	 * @param $convertUnits
	 * @return string
	 */
	private function formatCSV( $convertUnits ) {
		$str = '';
		foreach ( $convertUnits as $name => $data ) {
			$str .= "$name,$data[0],$data[1]\n";
		}
		return $str;
	}

	/**
	 * Create DecimalValue from regular numeric string or value.
	 * @param int|float|string $number
	 * FIXME: replace with DecimalValue method from https://github.com/DataValues/Number/pull/67
	 * @return DecimalValue
	 */
	private function makeDecimalValue( $number ) {

		if ( is_string( $number ) && $number !== '' ) {
			if ( $number[0] !== '-' && $number[0] !== '+' ) {
				$number = '+' . $number;
			}
		}

		return new DecimalValue( $number );
	}

}

$maintClass = UpdateUnits::class;
require_once RUN_MAINTENANCE_IF_MAIN;
