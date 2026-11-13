<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

trait SharedTrait
{
    /**
     * @return bool|null
     */
    public function isShared()
    {
        return null;
    }
}
