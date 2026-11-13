<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

final class ConstructorDocblock
{
    /**
     * @return bool|null
     */
    public function __construct()
    {
    }

    /**
     * @return bool|null
     */
    public function __destruct()
    {
    }
}
