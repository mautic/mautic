<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullPropertyDocblockToNativeType;

class ParentWithUntypedChild
{
    /**
     * @var bool|null
     */
    protected $flag;
}
