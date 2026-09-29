<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BigIntIdParam;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<BigIntIdEntity>
 */
final class BigIntIdEntityRepository extends EntityRepository
{
    public function exists(int $id): bool
    {
        return true;
    }

    public function findByStringId(?string $id): void
    {
    }

    public function findByIntOrStringId(int|string $id): void
    {
    }

    public function findByNullableId(int|string|null $id): void
    {
    }

    public function findByUntypedId($id): void
    {
    }

    public function findByOtherParam(int $leadId): void
    {
    }
}
