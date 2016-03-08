<?php

namespace Wikibase\Repo\ParserOutput;

use OutOfBoundsException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityView;
use Wikimedia\Assert\Assert;

/**
 * A factory to create EntityView implementations by entity type based on callbacks.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DispatchingEntityViewFactory {

	/**
	 * @var callable[]
	 */
	private $entityViewFactoryCallbacks;

	/**
	 * @param callable[] $entityViewFactoryCallbacks
	 */
	public function __construct( array $entityViewFactoryCallbacks ) {
		Assert::parameterElementType( 'callable', $entityViewFactoryCallbacks, '$entityViewFactoryCallbacks' );

		$this->entityViewFactoryCallbacks = $entityViewFactoryCallbacks;
	}

	/**
	 * Creates a new EntityView that can display the given type of entity.
	 *
	 * @param string $entityType
	 * @param string $uiLanguageCode
	 * @param string $contentLanguageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 *
	 * @throws OutOfBoundsException
	 * @return EntityView
	 */
	public function newEntityView(
		$entityType,
		$uiLanguageCode,
		$contentLanguageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $languageFallbackChain,
		EditSectionGenerator $editSectionGenerator
	) {
		if ( !isset( $this->entityViewFactoryCallbacks[$entityType] ) ) {
			throw new OutOfBoundsException( "No EntityView is registered for entity type '$entityType'" );
		}

		$entityView = call_user_func(
			$this->entityViewFactoryCallbacks[$entityType],
			$uiLanguageCode,
			$contentLanguageCode,
			$labelDescriptionLookup,
			$languageFallbackChain,
			$editSectionGenerator
		);

		Assert::postcondition(
			$entityView instanceof EntityView,
			'Callback must return an instance of EntityView'
		);

		return $entityView;
	}

}
