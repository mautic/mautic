<?php

namespace Mautic\ConfigBundle\Mapper\Helper;

class ConfigHelper
{
    /**
     * Map local config values with form fields.
     *
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
