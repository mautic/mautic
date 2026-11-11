<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

interface SkippedInterface
{
    /**
     * @return bool|null
     */
    public function isActive();
}
