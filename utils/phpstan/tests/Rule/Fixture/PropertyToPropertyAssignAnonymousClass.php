<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

abstract class PropertyToPropertyAssignParent
{
    protected \stdClass $repository;
}

final class PropertyToPropertyAssignAnonymousClass
{
    public function create(\stdClass $repository): PropertyToPropertyAssignParent
    {
        return new class($repository) extends PropertyToPropertyAssignParent {
            public function __construct(
                private readonly \stdClass $testRepository,
            ) {
                $this->repository = $this->testRepository;
            }
        };
    }
}
