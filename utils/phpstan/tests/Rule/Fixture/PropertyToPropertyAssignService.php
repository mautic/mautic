<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class PropertyToPropertyAssignService
{
    private readonly \stdClass $repository;

    private int $count = 0;

    private int $previousCount = 0;

    public function __construct(
        private readonly \stdClass $someRepository,
    ) {
        $this->repository = $this->someRepository;
    }

    public function increase(): void
    {
        ++$this->count;
    }

    public function rememberPreviousCount(): void
    {
        $this->previousCount = $this->count;
    }

    public function useLocalVariable(): void
    {
    }
}
