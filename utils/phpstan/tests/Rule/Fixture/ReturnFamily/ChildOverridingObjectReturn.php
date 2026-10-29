<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ReturnFamily;

use Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject;

final class ChildOverridingObjectReturn extends BaseWithObjectReturn
{
    /**
     * @return SomeReturnObject
     */
    public function build()
    {
        return new SomeReturnObject();
    }
}
