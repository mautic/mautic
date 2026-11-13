<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

final class ReportedImplementation implements ReportedInterface
{
    /**
     * @return bool|null
     */
    public function isActive()
    {
        return null;
    }
}
