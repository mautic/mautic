<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class ImplicitNullableParam
{
    public function implicitClass(SomeValueObject $item = null): void
    {
    }

    public function implicitScalar(string $name = null): void
    {
    }

    public function alreadyExplicit(?SomeValueObject $item = null): void
    {
    }

    public function unionWithNull(SomeValueObject|null $item = null): void
    {
    }

    public function noDefault(SomeValueObject $item): void
    {
    }

    public function nonNullDefault(int $limit = 10): void
    {
    }

    public function mixedType(mixed $value = null): void
    {
    }
}
