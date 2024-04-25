<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Value;

class ReferenceValueDAO implements \Stringable
{
    private ?int $value = null;

    private ?string $type = null;

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        return [
            'value' => $this->value,
            'types' => $this->type,
        ];
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        $this->value = $data['value'] ?? null;
        $this->type  = $data['type'] ?? null;
    }
}
