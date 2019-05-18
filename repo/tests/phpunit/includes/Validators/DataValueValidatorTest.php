<?php

namespace Wikibase\Repo\Tests\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\Repo\Validators\DataValueValidator;
use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\DataValueValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DataValueValidatorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function provideValidate() {
		$validator = new StringLengthValidator( 1, 10 );

		return [
			[ $validator, new StringValue( '' ), false, null, "mismatch" ],
			[ $validator, new StringValue( 'foo' ), true, null, "match" ],
			[ $validator, 'xyz', false, InvalidArgumentException::class, 'not a DataValue' ],
		];
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $validator, $value, $expected, $exception, $message ) {
		if ( $exception !== null ) {
			$this->setExpectedException( $exception );
		}

		$validator = new DataValueValidator( $validator );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}
