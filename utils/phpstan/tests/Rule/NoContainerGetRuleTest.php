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
                'Do not fetch a service via $container->get(...). Inject the service as a typed constructor property instead.',
                14,
            ],
            [
                'Do not fetch a service via $this->get(...). Inject the service as a typed constructor property instead.',
                19,
            ],
        ]);

        // unrelated ArrayCollection->get() must be skipped
        $this->analyse([__DIR__.'/Fixture/AllowedGetService.php'], []);
    }
}
