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

		$terms = StoreFactory::getStore()->newTermCache()->getMatchingTerms(
			array(
				new Term( array(
					'termType' 		=> Term::TYPE_LABEL,
					'termLanguage' 	=> $language,
					'termText' 		=> $term
				) ),
				new Term( array(
					'termType' 		=> Term::TYPE_ALIAS,
					'termLanguage' 	=> $language,
					'termText' 		=> $term
				) )
			),
			null,
			$entityType,
			array(
				'caseSensitive' => false,
				'prefixSearch' => true,
			)
		);

		$entities = array();

		/**
		 * @var Term $term
		 */
		foreach ( $terms as $term ) {
			$entity = \Wikibase\EntityContentFactory::singleton()->getFromId( $entityType, $term->getEntityId() );

			if ( $entity !== null ) {
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	/**
	 * Order returned search array by score
	 *
	 * @since 0.2
	 *
	 * @param array $results
	 * @param string $language
	 * @param string $search
	 *
	 * @return array $entries
	 */

	private function sortByScore( $results, $language, $search ) {
		$entries = array();
		foreach ( $results as $result ) {
			$score = 0;
			$entry = array();
			$entity = $result->getEntity();
			$entry['id'] = $entity->getPrefixedId();
			if ( $entity->getLabel( $language ) !== false ) {
				$entry['label'] = $entity->getLabel( $language );
				$score = strlen( $search ) / strlen( $entity->getLabel( $language ) );
			}
			if ( $entity->getDescription( $language ) !== false ) {
				$entry['description'] = $entity->getDescription( $language );
			}

			$entry['aliases'] = $entity->getAliases( $language, $search );
			foreach ( $entry['aliases'] as $key => $value ) {
				if ( preg_match( "/^" . $search . "/i", $entry['aliases'][$key] ) === 0 ) {
					unset( $entry['aliases'][$key] );
				}
			}
			foreach ( $entry['aliases'] as $alias ) {
				$aliasscore = strlen( $search ) / strlen( $alias );
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
		// Do the actual sort by score
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

		return $entries;
	}

	/**
	 * @see ApiBase::execute()
	*/
	public function execute() {
		$params = $this->extractRequestParams();
		$entries = $this->sortByScore( $this->searchEntities( $params['language'], $params['search'], $params['type'] ), $params['language'], $params['search'] );

		$this->getResult()->addValue(
			null,
			'searchinfo',
			array(
				'totalhits' => count( $entries ),
				'search' => $params['search']
			)
		);

		$this->getResult()->addValue(
			null,
			'search',
			array()
		);

		if ( count( $entries ) >= $params['limit'] && ( $params['limit'] !== 0 ) ) {
			$continue = $params['continue'];
			$limit = $params['limit'];
			if ( ( $continue + $limit ) <= ( count( $entries ) - 1) ) {
				$searchcontinue['search']['continue'] = $continue + $limit +1;
			}
			if ( count( $entries ) -1 >= $continue + $limit ) {
				$entries = array_slice( $entries, $continue, $limit );
			} else {
				$entries = array_slice( $entries, $continue -1 );
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

		if ( isset( $searchcontinue ) && ( ( count( $entries ) -1 )  >= $params['limit'] )) {
			$this->getResult()->addValue(
				null,
				'search-continue',
				$searchcontinue
			);
		}
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		// TODO: We probably need a flag for fuzzy searches. This is
		// only a boolean flag.
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
				ApiBase::PARAM_DFLT => 7,
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
