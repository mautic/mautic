<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoUnusedControllerActionParameterRule;

/**
 * @extends RuleTestCase<NoUnusedControllerActionParameterRule>
 */
final class NoUnusedControllerActionParameterRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUnusedControllerActionParameterRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/UnusedControllerActionParameterController.php'], [
            [
                'Controller action "unsubscribeAction()" declares parameter "$leadModel" that is never used. Remove it.',
                11,
            ],
            [
                'Controller action "__invoke()" declares parameter "$unusedName" that is never used. Remove it.',
                17,
            ],
        ]);
    }

    public function testSkipUsedByRefAndScopeReadingAndNonAction(): void
    {
        $this->analyse([__DIR__.'/Fixture/UsedControllerActionParameterController.php'], []);
    }

    public function testSkipSharedBaseController(): void
    {
        $this->analyse([__DIR__.'/Fixture/AbstractUnusedParameterController.php'], []);
    }

    public function testSkipNonControllerClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeAutowireService.php'], []);
    }
}
