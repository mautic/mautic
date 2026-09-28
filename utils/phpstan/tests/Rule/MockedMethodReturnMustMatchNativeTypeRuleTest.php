<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\MockedMethodReturnMustMatchNativeTypeRule;
use Utils\PHPStan\Tests\Rule\Fixture\MockReturn\SomeMockedService;

/**
 * @extends RuleTestCase<MockedMethodReturnMustMatchNativeTypeRule>
 */
final class MockedMethodReturnMustMatchNativeTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new MockedMethodReturnMustMatchNativeTypeRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/MockReturn/MismatchedMockReturn.php'], [
            [
                sprintf('Mocked method "%s::getItems()" returns "array", but "string" is passed to willReturn().', SomeMockedService::class),
                14,
            ],
            [
                sprintf('Mocked method "%s::getName()" returns "string", but "array<int, string>" is passed to willReturn().', SomeMockedService::class),
                18,
            ],
            [
                sprintf('Mocked method "%s::getNullableName()" returns "string|null", but "int" is passed to willReturn().', SomeMockedService::class),
                21,
            ],
        ]);
    }

    public function testSkipMatchingReturn(): void
    {
        $this->analyse([__DIR__.'/Fixture/MockReturn/SkipMatchingMockReturn.php'], []);
    }
}
