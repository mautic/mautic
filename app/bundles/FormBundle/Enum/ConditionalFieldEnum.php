<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Enum;

class ConditionalFieldEnum
{
    private static $conditionalFieldTypes = ['select', 'country', 'checkboxgrp'];

    /**
     * @return array
     */
    public static function getConditionalFieldTypes()
    {
        return self::$conditionalFieldTypes;
    }
}
