<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ParamFamily;

use Utils\PHPStan\Tests\Rule\Fixture\SomeValueObject;

class BaseWithObjectParam
{
    /**
     * @param SomeValueObject $item
     */
    public function handle($item): void
    {
        $item->doThing();
    }
}
