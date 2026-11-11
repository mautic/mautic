<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

interface ReportedInterface
{
    /**
     * @return bool|null
     */
    public function isActive();
}
