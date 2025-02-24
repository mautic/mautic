<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ColumnCollectEvent extends Event
{
    /**
     * @var array<string, mixed>
     */
    private array $columns;

    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(
        private string $object,
        private array $properties = []
    ) {
        $this->columns = [];
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
     * @param array<string, array<string, mixed>> $column
     */
    public function addColumns(array $column): void
    {
        $this->columns = array_merge($this->columns, $column);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
