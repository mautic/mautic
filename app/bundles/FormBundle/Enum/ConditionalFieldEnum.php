<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Enum;

class ConditionalFieldEnum
{
    /**
     * @var string[]
     */
    private static $conditionalFieldTypes = ['select', 'country', 'checkboxgrp', 'radiogrp'];

    /**
     * @return string[]
     */
    public static function getConditionalFieldTypes(): array
    {
        return self::$conditionalFieldTypes;
    }
}
