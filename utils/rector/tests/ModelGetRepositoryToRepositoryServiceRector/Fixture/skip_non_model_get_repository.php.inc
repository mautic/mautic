<?php

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Fixture;

use Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source\SomeRepository;

final class SkipNonModelGetRepository
{
    public function getRepository(): SomeRepository
    {
        return new SomeRepository();
    }

    public function findThing(int $id): ?object
    {
        return $this->getRepository()->findThing($id);
    }
}
