<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullPropertyDocblockToNativeType;

class SomeParent
{
    /**
     * @var bool|null
     */
    protected $inherited;
}
