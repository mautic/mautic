<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ReturnFamilyBlocked;

use Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject;

final class ChildBlocked extends BaseBlocked
{
    // returns null too, so this override is not promotable and must hold back the whole family
    public function build()
    {
        return random_int(0, 1) ? new SomeReturnObject() : null;
    }
}
