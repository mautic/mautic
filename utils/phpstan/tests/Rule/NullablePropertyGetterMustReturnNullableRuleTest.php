<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NullablePropertyGetterMustReturnNullableRule;

/**
 * @extends RuleTestCase<NullablePropertyGetterMustReturnNullableRule>
 */
final class NullablePropertyGetterMustReturnNullableRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NullablePropertyGetterMustReturnNullableRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NullablePropertyGetter.php'], [
            [
                'Getter "getValue()" returns nullable property "$value" but declares a non-nullable return type. Make the return type nullable.',
                21,
            ],
            [
                'Getter "getName()" returns nullable property "$name" but declares a non-nullable return type. Make the return type nullable.',
                26,
            ],
        ]);
    }
}
