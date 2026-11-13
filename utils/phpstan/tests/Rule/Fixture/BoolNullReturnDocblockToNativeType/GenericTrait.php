<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

/**
 * @template T
 */
trait GenericTrait
{
    /**
     * @return T|null
     */
    public function getGenericValue()
    {
        return null;
    }
}
