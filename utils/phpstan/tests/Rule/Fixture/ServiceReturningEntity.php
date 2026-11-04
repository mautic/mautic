<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Utils\PHPStan\Tests\Rule\Fixture\Entity\EntityWithObjectReturn;

final class ServiceReturningEntity
{
    private EntityWithObjectReturn $entity;

    /**
     * @return EntityWithObjectReturn
     */
    public function get()
    {
        return $this->entity;
    }
}
