<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DTO;

final class GlobalSearchFilterDTO
{
    /**
     * @var array<int|string, mixed>
     */
    private array $filters = [];

    public function __construct(private string $searchString)
    {
    }

    public function getSearchString(): string
    {
        return $this->searchString;
    }

    /**
     * @return array<string, string|array<int, mixed>>
     */
    public function getFilters(): array
    {
        $filter = [
            'string' => $this->searchString,
            'force'  => [],
        ];

        return array_merge($filter, $this->filters);
    }

    /**
     * @param array<mixed> $filters
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }
}
