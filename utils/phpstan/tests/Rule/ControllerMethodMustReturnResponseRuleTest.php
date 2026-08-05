<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\ControllerMethodMustReturnResponseRule;

/**
 * @extends RuleTestCase<ControllerMethodMustReturnResponseRule>
 */
final class ControllerMethodMustReturnResponseRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ControllerMethodMustReturnResponseRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/MissingResponseReturnTypeController.php'], [
            [
                'Controller action "indexAction()" must return a Response, either alone or as part of a union.',
                9,
            ],
            [
                'Controller action "nameAction()" must return a Response, either alone or as part of a union.',
                14,
            ],
        ]);
    }

    public function testSkipResponseReturnTypeAndNonAction(): void
    {
        $this->analyse([__DIR__.'/Fixture/ResponseReturnTypeController.php'], []);
    }

    public function testSkipNonControllerClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeAutowireService.php'], []);
    }
}
