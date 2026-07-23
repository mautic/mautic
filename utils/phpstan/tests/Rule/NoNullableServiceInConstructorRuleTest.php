<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoNullableServiceInConstructorRule;

/**
 * @extends RuleTestCase<NoNullableServiceInConstructorRule>
 */
final class NoNullableServiceInConstructorRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoNullableServiceInConstructorRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NullableServiceConstructor.php'], [
            [
                'Constructor service "$someModel" of type "Utils\PHPStan\Tests\Rule\Fixture\SomeModel" is nullable. A service is always provided, make it non-nullable.',
                10,
            ],
            [
                'Constructor service "$someService" of type "Utils\PHPStan\Tests\Rule\Fixture\SomeAutowireService" is nullable. A service is always provided, make it non-nullable.',
                11,
            ],
        ]);
    }

    public function testSkipNullableScalarAndArray(): void
    {
        $this->analyse([__DIR__.'/Fixture/NullableScalarConstructor.php'], []);
    }

    public function testSkipNullableThrowable(): void
    {
        $this->analyse([__DIR__.'/Fixture/NullableExceptionConstructor.php'], []);
    }

    public function testSkipAnonymousClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/NullableServiceAnonymousClass.php'], []);
    }

    public function testSkipNullableDateTime(): void
    {
        $this->analyse([__DIR__.'/Fixture/NullableDateTimeConstructor.php'], []);
    }

    public function testSkipClassInDataHolderNamespace(): void
    {
        $this->analyse([__DIR__.'/Fixture/Entity/NullableServiceInEntity.php'], []);
    }

    public function testSkipNullableWhenSiblingParamHasSameType(): void
    {
        $this->analyse([__DIR__.'/Fixture/DuplicateTypeConstructor.php'], []);
    }

    public function testSkipBadgeClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/NullableServiceInBadge.php'], []);
    }
}
