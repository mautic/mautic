<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NullableObjectPropertyMustHaveNativeTypeRule;

/**
 * @extends RuleTestCase<NullableObjectPropertyMustHaveNativeTypeRule>
 */
final class NullableObjectPropertyMustHaveNativeTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NullableObjectPropertyMustHaveNativeTypeRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NullableObjectPropertyMustHaveNativeType/SomeClass.php'], [
            [
                'Property "$ipAddress" should declare native type "?IpAddress = null" instead of a "@var" docblock only.',
                12,
            ],
            [
                'Property "$createdAt" should declare native type "?DateTimeInterface = null" instead of a "@var" docblock only.',
                17,
            ],
            [
                'Property "$prefixNullable" should declare native type "?IpAddress = null" instead of a "@var" docblock only.',
                49,
            ],
        ]);
    }
}
