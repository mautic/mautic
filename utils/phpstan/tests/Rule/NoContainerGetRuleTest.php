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
                19,
            ],
            [
                'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.',
                24,
            ],
            [
                'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.',
                29,
            ],
            [
                'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.',
                34,
            ],
        ]);

        // ArrayCollection->get() and an own get() method must be skipped
        $this->analyse([__DIR__.'/Fixture/AllowedGetService.php'], []);

        // a scoped ServiceLocator is an allowed, explicit set of services
        $this->analyse([__DIR__.'/Fixture/ServiceLocatorGetService.php'], []);
    }
}
