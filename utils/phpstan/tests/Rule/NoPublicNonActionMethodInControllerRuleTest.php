<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoPublicNonActionMethodInControllerRule;

/**
 * @extends RuleTestCase<NoPublicNonActionMethodInControllerRule>
 */
final class NoPublicNonActionMethodInControllerRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoPublicNonActionMethodInControllerRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/PublicNonActionMethodController.php'], [
            [
                'Controller method "getModelName()" is not a route action, so it cannot be public. Make it protected or private.',
                11,
            ],
        ]);
    }

    public function testSkipActionsAndContracts(): void
    {
        $this->analyse([__DIR__.'/Fixture/SkipPublicActionMethodController.php'], []);
    }

    public function testSkipEventSubscriber(): void
    {
        $this->analyse([__DIR__.'/Fixture/SkipEventSubscriberController.php'], []);
    }

    public function testSkipNonControllerClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeAutowireService.php'], []);
    }
}
