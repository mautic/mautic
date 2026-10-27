<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoGetModelWithStringInControllerRule;

/**
 * @extends RuleTestCase<NoGetModelWithStringInControllerRule>
 */
final class NoGetModelWithStringInControllerRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoGetModelWithStringInControllerRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/GetModelByStringController.php'], [
            [
                'Controller must not fetch the "lead" model by string. Inject the model as a typed property instead, to make the dependency and its type explicit.',
                12,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/GetModelByStringService.php'], []);

        // a trait would be reported in every controller using it, in a file that does not contain the call
        $this->analyse([__DIR__.'/Fixture/GetModelByStringTrait.php', __DIR__.'/Fixture/GetModelByStringTraitUsingController.php'], []);
    }
}
