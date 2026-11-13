<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

final class UntypedImplementation implements SkippedInterface
{
    /**
     * @return mixed
     */
    public function isActive()
    {
        return null;
    }
}
