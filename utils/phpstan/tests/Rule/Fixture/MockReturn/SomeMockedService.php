<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\MockReturn;

class SomeMockedService
{
    public function getItems(): array
    {
        return [];
    }

    public function getName(): string
    {
        return '';
    }

    public function getNullableName(): ?string
    {
        return null;
    }

    public function getSelf(): self
    {
        return $this;
    }

    public function setName(string $name): static
    {
        return $this;
    }

    public function getUntyped()
    {
        return null;
    }
}
