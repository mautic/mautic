<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

class ParentWithTypedChild
{
    /**
     * @return bool|null
     */
    public function isActive()
    {
        return null;
    }
}
