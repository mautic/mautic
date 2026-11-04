<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\Entity;

use Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject;

final class EntityWithObjectReturn
{
    private SomeReturnObject $related;

    /**
     * @return SomeReturnObject
     */
    public function getRelated()
    {
        return $this->related;
    }
}
