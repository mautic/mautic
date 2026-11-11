<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

class SomeClass extends SomeParent
{
    /**
     * @return bool|null
     */
    public function getIsPreferenceCenter()
    {
        return null;
    }

    /**
     * @return ?bool
     */
    public function isShortNullable()
    {
        return true;
    }

    /**
     * @return bool|null
     */
    public function alreadyTyped(): ?bool
    {
        return null;
    }

    /**
     * @return bool
     */
    public function plainBool()
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function otherNullable()
    {
        return null;
    }

    /**
     * @return bool|null
     */
    public function overridden()
    {
        return null;
    }
}
