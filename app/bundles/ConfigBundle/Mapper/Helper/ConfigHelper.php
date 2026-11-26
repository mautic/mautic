<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Mapper\Helper;

final class ConfigHelper
{
    /**
     * Map local config values with form fields.
     */
    public static function bindNestedConfigValues(array $configValues, array|string|int|float|bool|null $defaults): array
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

            $configValues[$key] ??= $defaultValue;
        }

        return $configValues;
    }
}
