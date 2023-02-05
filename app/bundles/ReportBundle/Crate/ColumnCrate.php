<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Crate;

final class ColumnCrate
{
    private string $key;
    private string $label;
    private string $type;
    /**
     * @var array<string, mixed>
     */
    private array $properties;

    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(string $key, string $label, string $type, array $properties)
    {
        $this->key        = $key;
        $this->label      = $label;
        $this->type       = $type;
        $this->properties = $properties;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}