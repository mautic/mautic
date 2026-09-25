<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class SomeValue
{
}

final class NullablePropertyGetter
{
    private ?SomeValue $value = null;

    private ?SomeValue $honest = null;

    private ?string $name = null;

    private SomeValue $required;

    public function getValue(): SomeValue
    {
        return $this->value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHonest(): ?SomeValue
    {
        return $this->honest;
    }

    public function getRequired(): SomeValue
    {
        return $this->required;
    }

    public function getValueOrDefault(): SomeValue|null
    {
        return $this->value;
    }

    private function getSecret(): SomeValue
    {
        return $this->value;
    }

    public function value(): SomeValue
    {
        return $this->value;
    }
}
