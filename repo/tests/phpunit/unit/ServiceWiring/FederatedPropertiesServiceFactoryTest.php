<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashConfig;
use LogicException;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\FederatedProperties\ApiServiceFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesServiceFactoryTest extends ServiceWiringTestCase {

	public function testFederatedPropertiesEnabled(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'federatedPropertiesEnabled' => true,
				'federatedPropertiesSourceScriptUrl' => 'https://wiki.example/w/',
			] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'ServerName' => 'https://other-wiki.example/w/',
			] ) );

		$this->assertInstanceOf(
			ApiServiceFactory::class,
			$this->getService( 'WikibaseRepo.FederatedPropertiesServiceFactory' )
		);
	}

	/** @dataProvider provideSettingsWithoutFederatedProperties */
	public function testFederatedPropertiesNotEnabled( array $settings ): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( $settings ) );

		$this->expectException( LogicException::class );
		$this->getService( 'WikibaseRepo.FederatedPropertiesServiceFactory' );
	}

	public function provideSettingsWithoutFederatedProperties(): iterable {
		yield 'federated properties not enabled' => [ [
			'federatedPropertiesEnabled' => false,
		] ];
		yield 'source script URL not configured' => [ [
			'federatedPropertiesEnabled' => true,
		] ];
	}

}
