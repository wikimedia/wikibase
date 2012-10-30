<?php

namespace Wikibase;
use ApiBase;

/**
 * API module to search for Wikibase entities.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */
class ApiSearchEntities extends ApiBase {

	/**
	 * Get the entities corresponding to the provided language and term pair.
	 * Term means it is either a label or an alias.
	 *
	 *
	 * @since 0.2
	 *
	 * @param string $language
	 * @param string $term
	 * @param string|null $entityType
	 *
	 * @return array of EntityContent
	 */
	public function searchEntities( $language, $term, $entityType = null ) {

		$hits = StoreFactory::getStore()->newTermCache()->getMatchingTerms(
			array(
				array(
					'termType' 		=> TermCache::TERM_TYPE_LABEL,
					'termLanguage' 	=> $language,
					'termText' 		=> $term
				),
				array(
					'termType' 		=> TermCache::TERM_TYPE_ALIAS,
					'termLanguage' 	=> $language,
					'termText' 		=> $term
				)
			),
			null,
			$entityType,
			true // Do a prefix search!
		);

		$entities = array();

		foreach ( $hits as $hit ) {
			$entity = \Wikibase\EntityContentFactory::singleton()->getFromId( $entityType, $hit['entityId'] );

			if ( !is_null( $entity ) ) {
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	/**
	 * @see ApiBase::execute()
	*/
	public function execute() {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$params = $this->extractRequestParams();
		$hits = $this->searchEntities( $params['language'], $params['search'], $params['type'] );

		$this->getResult()->addValue(
			null,
			'searchinfo',
			array(
				'totalhits' => count( $hits ),
				'search' => $params['search']
			)
		);

		$this->getResult()->addValue(
			null,
			'search',
			array()
		);

		$entries = array();
		foreach ( $hits as $hit ) {
			$score = 0;
			$entry = array();
			$entity = $hit->getEntity();
			$entry['id'] = $entity->getId();
			if ( $entity->getLabel( $params['language'] ) !== false ) {
				$entry['labels'] = $entity->getLabel( $params['language'] );
				$score = strlen( $params['search'] ) / strlen( $entity->getLabel( $params['language'] ) );
			}
			if ( $entity->getDescription( $params['language'] ) !== false ) {
				$entry['description'] = $entity->getDescription( $params['language'] );
			}

			$entry['aliases'] = $entity->getAliases( $params['language'], $params['search'] );
			foreach ( $entry['aliases'] as $key => $value ) {
				if ( preg_match( "/^" . $params['search'] . "/i", $entry['aliases'][$key] ) === 0 ) {
					unset( $entry['aliases'][$key] );
				}
			}
			foreach ( $entry['aliases'] as $alias ) {
				$aliasscore = strlen( $params['search'] ) / strlen( $alias );
				if ( $aliasscore > $score ) {
					$score = $aliasscore;
				}
			}
			$this->getResult()->setIndexedTagName( $entry['aliases'], 'alias' );
			if ( $score > 0 ) {
				$entry['score'] = $score;
			}
			if ( !in_array( $entry, $entries ) ) {
				$entries[] = $entry;
			}
		}

		// Sort by score
		$sortArray = array();
		foreach( $entries as $entry ){
			foreach( $entry as $key=>$value){
				if( !isset( $sortArray[$key] ) ){
					$sortArray[$key] = array();
				}
				$sortArray[$key][] = $value;
			}
		}
		$orderby = "score";
		if ( $entries !== array() ) array_multisort( $sortArray[$orderby], SORT_DESC, $entries );

		// Do continuation and pass offset in search-continue structure if limit isn't 0
		if ( $params['limit'] !== 0 ) {
			$searchcontinue = array();
			$searchcontinue['search']['continue'] = $params['continue'] + $params['limit'] +1;
			if ( $params['continue'] !== 0 ) {
				$entries = array_slice($entries, $params['continue'], ( $searchcontinue['search']['continue'] + $params['limit'] ) - count( $entries ) ) ;
			} else {
				$entries = array_slice($entries, 0, $params['limit']);
			}
			if ( $searchcontinue['search']['continue'] < count( $hits ) || $searchcontinue['search']['continue'] === count( $hits ) ) {
				if ( $searchcontinue['search']['continue'] === count( $hits ) ) {
					$searchcontinue['search']['continue'] =  $searchcontinue['search']['continue'] -1;
				}

			}
		}

		$this->getResult()->addValue(
			null,
			'search',
			$entries
		);
		$this->getResult()->setIndexedTagName_internal( array( 'search' ), 'entity' );

		$this->getResult()->addValue(
			null,
			'success',
			(int)true
		);

		if ( isset( $searchcontinue ) && ( sizeof( $entries ) === $params['limit'] )) {
			$this->getResult()->addValue(
				null,
				'search-continue',
				$searchcontinue
			);
		}
		wfProfileOut( "Wikibase-" . __METHOD__ );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		// TODO: We probably need a flag for fuzzy searches. This is
		// only a boolean flag.
		// TODO: We need paging, and this can be done at least
		// in two different ways. Initially we make the implementation
		// without paging.
		return array(
			'search' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'type' => array(
				ApiBase::PARAM_TYPE => EntityFactory::singleton()->getEntityTypes(),
				ApiBase::PARAM_DFLT => 'item',
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
			),
			'continue' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
			),
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 */
	public function getParamDescription() {
		// we probably need a flag for fuzzy searches
		return array(
			'search' => 'Search for this initial text.',
			'language' => 'Search within this language.',
			'type' => 'Search for this type of entity.',
			'limit' => array( 'Limit to this number of non-exact matches',
				"The value '0' will return all found matches." ),
			'continue' => 'Offset where to continue when in a (limited) search continuation',
		);
	}

	/**
	 * @see ApiBase::getDescription
	 */
	public function getDescription() {
		return array(
			'API module to search for entities.'
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	/**
	 * @see ApiBase::getExamples
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsearchentities&search=abc&language=en'
			=> 'Search for "abc" in English language, with defaults for type and limit.',
		);
	}

	/**
	 * @see ApiBase::getHelpUrls
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsearchentity';
	}

	/**
	 * @see ApiBase::getVersion
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
