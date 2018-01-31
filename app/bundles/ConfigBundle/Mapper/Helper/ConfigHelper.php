<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Mapper\Helper;

class ConfigHelper
{
    /**
     * Map local config values with form fields.
     *
     * @param array $configValues
     * @param mixed $defaults
     *
     * @return array
     */
    public static function bindNestedConfigValues(array $configValues, $defaults)
    {
        if (!is_array($defaults)) {
            // Return all config values
            return $configValues;
        }

        foreach ($defaults as $key => $defaultValue) {
            if (isset($configValues[$key]) && is_array($configValues[$key])) {
                $configValues[$key] = self::bindNestedConfigValues($configValues[$key], $defaultValue);

                continue;
            }

            $configValues[$key] = (isset($configValues[$key])) ? $configValues[$key] : $defaultValue;
        }

        return $configValues;
    }
}
