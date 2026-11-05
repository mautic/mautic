<?php

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Fixture;

use Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source\SomeModel;

final class ServiceInjectsModelRepository
{
    public function __construct(private readonly SomeModel $someModel)
    {
    }

    public function findThing(int $id): ?object
    {
        return $this->someModel->getRepository()->findThing($id);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Fixture;

use Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source\SomeModel;

final class ServiceInjectsModelRepository
{
    public function __construct(private readonly SomeModel $someModel, private readonly \Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source\SomeRepository $someRepository)
    {
    }

    public function findThing(int $id): ?object
    {
        return $this->someRepository->findThing($id);
    }
}

?>
