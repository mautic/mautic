<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Exception\ValueNotMergeable;

class MergeValueHelper
{
    /**
     * @param $newerValue
     * @param $olderValue
     *
     * @throws ValueNotMergeable
     */
    public static function getMergeValue($newerValue, $olderValue)
    {
        if ($newerValue === $olderValue) {
            throw new ValueNotMergeable($newerValue, $olderValue);
        }

        if (self::isNotEmpty($newerValue)) {
            return $newerValue;
        }

        if (self::isNotEmpty($olderValue)) {
            return $olderValue;
        }

        throw new ValueNotMergeable($newerValue, $olderValue);
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public static function isNotEmpty($value)
    {
        return null !== $value && '' !== $value;
    }
}
