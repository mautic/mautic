<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoContainerGetRule;

/**
 * @extends RuleTestCase<NoContainerGetRule>
 */
final class NoContainerGetRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoContainerGetRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ContainerGetService.php'], [
            [
                'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.',
                20,
            ],
            [
                'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.',
                25,
            ],
            [
                'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.',
                30,
            ],
            [
                'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.',
                35,
            ],
        ]);

        // ArrayCollection->get() and an own get() method must be skipped
        $this->analyse([__DIR__.'/Fixture/AllowedGetService.php'], []);

        // a scoped ServiceLocator is an allowed, explicit set of services
        $this->analyse([__DIR__.'/Fixture/ServiceLocatorGetService.php'], []);

        // in a test the container fetch is allowed, but only by a class constant
        $this->analyse([__DIR__.'/Fixture/ContainerGetInTestCase.php'], [
            [
                'Do not fetch a service from the container by a string name. Use a class constant, e.g. ->get(SomeService::class), to get a typed service.',
                20,
            ],
        ]);
    }
}
