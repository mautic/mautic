<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<EntityWithCustomRepository>
 */
class CustomEntityRepository extends EntityRepository
{
}
