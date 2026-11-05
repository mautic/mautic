<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ReturnFamilyBlocked;

use Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject;

abstract class BaseBlocked
{
    /**
     * @return SomeReturnObject
     */
    public function build()
    {
        return new SomeReturnObject();
    }
}
