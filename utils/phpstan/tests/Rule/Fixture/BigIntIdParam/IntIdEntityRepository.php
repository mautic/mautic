<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BigIntIdParam;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<IntIdEntity>
 */
final class IntIdEntityRepository extends EntityRepository
{
    public function exists(int $id): bool
    {
        return true;
    }
}
