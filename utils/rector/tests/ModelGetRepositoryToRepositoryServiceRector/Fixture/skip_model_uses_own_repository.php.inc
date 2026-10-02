<?php

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Fixture;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source\SomeRepository;

class SkipModelUsesOwnRepository extends AbstractCommonModel
{
    public function __construct(private readonly SomeRepository $someRepository)
    {
    }

    public function getRepository(): SomeRepository
    {
        return $this->someRepository;
    }

    public function findThing(int $id): ?object
    {
        return $this->getRepository()->findThing($id);
    }
}
