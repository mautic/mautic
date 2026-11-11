<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

final class VendorParentChild extends \ArrayObject
{
    /**
     * @return bool|null
     */
    public function count()
    {
        return null;
    }
}
