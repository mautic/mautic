<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\ExplicitNullableParamTypeRule;

/**
 * @extends RuleTestCase<ExplicitNullableParamTypeRule>
 */
final class ExplicitNullableParamTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ExplicitNullableParamTypeRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ImplicitNullableParam.php'], [
            [
                'Parameter "$item" is implicitly nullable. Declare it as "?Utils\PHPStan\Tests\Rule\Fixture\SomeValueObject".',
                9,
            ],
            [
                'Parameter "$name" is implicitly nullable. Declare it as "?string".',
                13,
            ],
        ]);
    }
}
