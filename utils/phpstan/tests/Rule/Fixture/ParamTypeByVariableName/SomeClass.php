<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ParamTypeByVariableName;

final class SomeClass
{
    public function missingType($parameters): void
    {
    }

    public function suffixMatch($queryParameters): void
    {
    }

    public function skipCorrect(array $parameters): void
    {
    }

    public function skipNullable(?array $parameters): void
    {
    }

    public function skipScalarType(string $parameters): void
    {
    }

    public function skipUnionType(array|string $parameters): void
    {
    }

    public function skipObjectType(\stdClass $parameters): void
    {
    }

    public function skipUnknownName(string $name): void
    {
    }
}
