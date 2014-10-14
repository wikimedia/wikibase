<?php

namespace Wikibase\Test;

use Exception;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * Mock implementation of TermIndex.
 *
 * @note: this uses internal knowledge about which functions of TermIndex are used
 * by PropertyLabelResolver, and how.
 *
 * @todo: make a fully functional mock conforming to the contract of the TermIndex
 * interface and passing tests for that interface. Only then will TermPropertyLabelResolverTest
 * be a true blackbox test.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockTermIndex implements TermIndex {

	/**
	 * @var Term[]
	 */
	protected $terms;

	/**
	 * @param Term[] $terms
	 */
	public function __construct( $terms ) {
		$this->terms = $terms;
	}

	/**
	 * @see LabelConflictFinder::getLabelConflicts
	 *
	 * @param string $entityType The relevant entity type
	 * @param string $labels The label to look for
	 * @param string|null $descriptions The description to consider, if descriptions are relevant.
	 * @param EntityId|null $excludeId Ignore conflicts with this entity ID (for ignoring self-conflicts)
	 *
	 * @return EntityId[]
	 */
	public function getLabelConflicts( $entityType, $labels, $descriptions = null, \Wikibase\DataModel\Entity\EntityId $excludeId = null ) {
		if ( $descriptions !== null ) {
			$labels = array_intersect_key( $labels, $descriptions );
			$descriptions = array_intersect_key( $descriptions, $labels );

			if ( empty( $descriptions ) ) {
				return array();
			}
		}

		if ( empty( $labels ) ) {
			return array();
		}

		$templates = $this->makeTemplateTerms( $labels, Term::TYPE_LABEL );

		$conflicts = $this->getMatchingTerms(
			$templates,
			Term::TYPE_LABEL,
			$entityType
		);

		if ( !empty( $labelConflictsEntities ) && $descriptions !== null ) {
			$templates = $this->makeTemplateTerms( $descriptions, Term::TYPE_DESCRIPTION );

			$descriptionConflicts = $this->getMatchingTerms(
				$templates,
				Term::TYPE_DESCRIPTION,
				$entityType
			);

			$conflicts = $this->intersectConflicts( $conflicts, $descriptionConflicts );
		}

		if ( $excludeId ) {
			$conflicts = $this->filterConflictsByEntity( $conflicts, $excludeId );
		}

		return $conflicts;
	}

	/**
	 * @param string[] $textsByLanguage A list of texts, or a list of lists of texts (keyed by language on the top level)
	 * @param string $type
	 *
	 * @return Term[]
	 */
	private function makeTemplateTerms( $textsByLanguage, $type ) {
		$terms = array();

		foreach ( $textsByLanguage as $lang => $texts ) {
			$texts = (array)$texts;

			foreach ( $texts as $text ) {
				$terms[] = new Term( array(
					'termText' => $text,
					'termLanguage' => $lang,
					'termType' => $type,
				) );
			}
		}

		return $terms;
	}

	/**
	 * @throws Exception always
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $entityType = null, $fuzzySearch = false ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function saveTermsOfEntity( Entity $entity ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function deleteTermsOfEntity( EntityId $entityId ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function getTermsOfEntity( EntityId $id ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function getTermsOfEntities( array $ids, $entityType, $language = null ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function termExists( $termValue, $termType = null, $termLanguage = null, $entityType = null ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * Implemented to fit the need of PropertyLabelResolver.
	 *
	 * @note: The $options parameters is ignored. The language to get is determined by the
	 * language of the first Term in $terms. $The termType and $entityType parameters are used,
	 * but the termType and entityType fields of the Terms in $terms are ignored.
	 *
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *
	 * @return Term[]
	 */
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null, array $options = array() ) {
		$matchingTerms = array();

		$language = $terms[0]->getLanguage();

		foreach ( $this->terms as $term ) {
			if ( ( $language === null || $term->getLanguage() === $language )
				&& ( $entityType === null || $term->getEntityType() === $entityType )
				&& ( $termType === null || $term->getType() === $termType )
				&& $this->termMatchesTemplates( $term, $terms )
			) {

				$matchingTerms[] = $term;
			}
		}

		return $matchingTerms;
	}

	/**
	 * @throws Exception always
	 */
	public function getMatchingIDs( array $terms, $entityType, array $options = array() ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function clear() {
		$this->terms = array();
	}

	/**
	 * Rekeys a list of Terms based on EntityId and language.
	 *
	 * @param Term[] $conflicts
	 *
	 * @return Term[]
	 */
	private function rekeyConflicts( array $conflicts ) {
		$rekeyed = array();

		foreach ( $conflicts as $term ) {
			$key = $term->getEntityId()->getSerialization();
			$key .= '/' . $term->getLanguage();

			$rekeyed[$key] = $term;
		}

		return $rekeyed;
	}

	/**
	 * Intersects two lists of Terms based on EntityId and language.
	 *
	 * @param Term[] $base
	 * @param Term[] $filter
	 *
	 * @return Term[]
	 */
	private function intersectConflicts( array $base, array $filter ) {
		$base = $this->rekeyConflicts( $base );
		$filter = $this->rekeyConflicts( $filter );

		return array_intersect( $base, $filter );
	}

	/**
	 * @param Term[] $conflicts
	 * @param EntityId $excludeId
	 *
	 * @return Term[]
	 */
	private function filterConflictsByEntity( array $conflicts, EntityId $excludeId ) {
		$filtered = array();

		foreach ( $conflicts as $key => $term ) {
			$entityId = $term->getEntityId();

			if ( $entityId === null || !$excludeId->equals( $entityId ) ) {
				$filtered[$key] = $term;
			}
		}

		return $filtered;
	}

	/**
	 * @param Term $term
	 * @param Term[] $templates
	 *
	 * @return bool
	 */
	private function termMatchesTemplates( Term $term, array $templates ) {
		foreach ( $templates as $template ) {
			if ( $template->getType() !== null && $template->getType() != $term->getType() ) {
				continue;
			}

			if ( $template->getEntityType() !== null && $template->getEntityType() != $term->getEntityType() ) {
				continue;
			}

			if ( $template->getLanguage() !== null && $template->getLanguage() != $term->getLanguage() ) {
				continue;
			}

			if ( $template->getText() !== null && $template->getText() != $term->getText() ) {
				continue;
			}

			if ( $template->getEntityId() !== null && !$template->getEntityId()->equals( $term->getEntityType() ) ) {
				continue;
			}

			return true;
		}

		return false;
	}
}
