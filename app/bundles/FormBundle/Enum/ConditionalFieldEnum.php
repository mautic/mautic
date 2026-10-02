<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Enum;

final class ConditionalFieldEnum
{
    /**
     * @var string[]
     */
    private static array $conditionalFieldTypes = ['select', 'country', 'checkboxgrp', 'radiogrp', 'boolean'];

    /**
     * @return string[]
     */
    public static function getConditionalFieldTypes(): array
    {
        return self::$conditionalFieldTypes;
    }
}
