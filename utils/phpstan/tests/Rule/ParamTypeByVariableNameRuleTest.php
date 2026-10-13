<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\ParamTypeByVariableNameRule;

/**
 * @extends RuleTestCase<ParamTypeByVariableNameRule>
 */
final class ParamTypeByVariableNameRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ParamTypeByVariableNameRule(['parameters' => 'array']);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ParamTypeByVariableName/SomeClass.php'], [
            [
                'Param "$parameters" of "missingType()" must be "array" based on its name.',
                9,
            ],
            [
                'Param "$queryParameters" of "suffixMatch()" must be "array" based on its name.',
                13,
            ],
        ]);
    }
}
