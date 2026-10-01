<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Source;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<SomeEntity>
 */
final class SomeRepository extends EntityRepository
{
}
