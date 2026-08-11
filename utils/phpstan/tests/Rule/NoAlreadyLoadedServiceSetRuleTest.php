<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoAlreadyLoadedServiceSetRule;

/**
 * @extends RuleTestCase<NoAlreadyLoadedServiceSetRule>
 */
final class NoAlreadyLoadedServiceSetRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoAlreadyLoadedServiceSetRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/LoadedServiceBundle/Config/services.php',
        ], [
            [
                'Service "Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\AlreadyLoadedService" is already registered by the $services->load("Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\") call above, remove the $services->set() call.',
                28,
            ],
            [
                'Service "Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\SecondAlreadyLoadedService" is already registered by the $services->load("Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\") call above, remove the $services->set() call.',
                29,
            ],
        ]);
    }

    public function testSkipConfigWithoutLoad(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ServiceNameBundle/Config/services.php',
        ], []);
    }

    public function testSkipNonServicesFile(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/LoadedServiceBundle/AlreadyLoadedService.php',
        ], []);
    }
}
