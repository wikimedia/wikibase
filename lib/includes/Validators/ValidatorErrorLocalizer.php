<?php

namespace Wikibase\Validators;

use Status;
use ValueValidators\Error;

/**
 * Class ValidatorErrorLocalizer
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ValidatorErrorLocalizer {

	/**
	 * Returns a Status representing the given errors.
	 * This can be used for reporting validation failures.
	 *
	 * @param Error[] $errors
	 * @return Status
	 */
	public function getErrorStatus( array $errors ) {
		$status = Status::newGood();

		foreach ( $errors as $error ) {
			$msg = $this->getErrorMessage( $error );
			$status->fatal( $msg );
		}

		return $status;
	}

	public function getErrorMessage( Error $error ) {
		// Messages:
		// wikibase-validator-bad-type, wikibase-validator-too-long, wikibase-validator-too-short,
		// wikibase-validator-too-high, wikibase-validator-too-low,
		// wikibase-validator-malformed-value, wikibase-validator-bad-entity-id,
		// wikibase-validator-bad-entity-type, wikibase-validator-no-such-entity,
		// wikibase-validator-no-such-property, wikibase-validator-bad-value,
		// wikibase-validator-bad-value-type, wikibase-validator-bad-url,
		// wikibase-validator-bad-url-scheme, wikibase-validator-bad-http-url,
		// wikibase-validator-bad-mailto-url, wikibase-validator-unknown-unit
		$key = 'wikibase-validator-' . $error->getCode();
		$params = $error->getParameters();

		//TODO: look for non-string in $params and run them through an appropriate formatter

		return wfMessage( $key, $params );
	}
}