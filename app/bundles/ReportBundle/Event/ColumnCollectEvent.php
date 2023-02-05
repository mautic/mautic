<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ColumnCollectEvent extends Event
{
    private string $object;
    private array $properties;
    /**
     * @var array<string, mixed>
     */
    private array $columns;

    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(string $object, array $properties = [])
    {
        $this->object     = $object;
        $this->properties = $properties;
        $this->columns    = [];
    }

    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param array<string, string|int> $column
     */
    public function addColumns(array $column): void
    {
        $this->columns = array_merge($this->columns, $column);
    }

    /**
     * @return array<string, string|int>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}