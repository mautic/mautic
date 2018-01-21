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

class RestrictionHelper
{
    /**
     * Ensure that the array has string indexes for congruency with a nested array similar to ['db_host', 'monitored_email' => ['EmailBundle_bounces'];.
     *
     * @param array $restrictedParameters
     *
     * @return array
     */
    public static function prepareRestrictions(array $restrictedParameters)
    {
        $prepared = [];
        foreach ($restrictedParameters as $key => $value) {
            $newKey            = (is_numeric($key)) ? $value : $key;
            $prepared[$newKey] = (is_array($value)) ? self::prepareRestrictions($value) : $value;
        }

        return $prepared;
    }

    /**
     * Remove fields that are restricted.
     *
     * @param array $configParameters
     * @param array $restrictedParameters
     *
     * @return array
     */
    public static function applyRestrictions(array $configParameters, array $restrictedParameters, $restrictedParentKey = null)
    {
        if ($restrictedParentKey) {
            if (!isset($restrictedParameters[$restrictedParentKey])) {
                // No restrictions
                return $configParameters;
            }

            $restrictedParameters = $restrictedParameters[$restrictedParentKey];
        }

        foreach ($configParameters as $key => $value) {
            // The entire form type is restricted
            if (isset($restrictedParameters[$key]) && !is_array($restrictedParameters[$key])) {
                unset($configParameters[$key]);

                continue;
            }

            // A sub type of the form type is restricted
            if (is_array($value)) {
                $configParameters[$key] = self::applyRestrictions($value, $restrictedParameters, $key);

                continue;
            }

            // Otherwise no restrictions are in place
        }

        return $configParameters;
    }
}
