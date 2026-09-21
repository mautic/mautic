<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<object>
 */
final class SomeRepository extends CommonRepository
{
    public function findThing(int $id): ?object
    {
        return $this->find($id);
    }
}
