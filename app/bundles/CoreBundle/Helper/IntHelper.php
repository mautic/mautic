<?php

namespace Mautic\CoreBundle\Helper;

class IntHelper
{
    public const MIN_INTEGER_VALUE = -2147483648;
    public const MAX_INTEGER_VALUE = 2147483647;

    public function getMaxIntValue(): int
    {
        return self::MAX_INTEGER_VALUE;
    }

    public function getMinIntValue(): int
    {
        return self::MIN_INTEGER_VALUE;
    }
}
