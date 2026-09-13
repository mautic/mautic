<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\ModelMethodSingleParamMustHaveTypeRule;

/**
 * @extends RuleTestCase<ModelMethodSingleParamMustHaveTypeRule>
 */
final class ModelMethodSingleParamMustHaveTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ModelMethodSingleParamMustHaveTypeRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/UntypedParamModel.php'], [
            [
                'Parameter "$entity" of model method "deleteEntity()" must declare a type.',
                9,
            ],
        ]);
    }

    public function testSkipNonModelClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeNonModelService.php'], []);
    }
}
