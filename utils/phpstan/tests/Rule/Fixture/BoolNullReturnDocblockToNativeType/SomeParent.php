<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

class SomeParent
{
    /**
     * @return bool|null
     */
    public function overridden()
    {
        return null;
    }
}
