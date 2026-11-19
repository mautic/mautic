<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\DTO;

final readonly class DetailRoute
{
    /**
     * @param array<string, scalar|\Stringable|null> $otherParameters
     */
    public function __construct(
        public string $route,
        private string $idParameterName,
        private array $otherParameters = [],
    ) {
    }

    /**
     * @return array<string, scalar|\Stringable|null>
     */
    public function getParameters(int|string|\Stringable $id): array
    {
        return [$this->idParameterName => $id] + $this->otherParameters;
    }
}
