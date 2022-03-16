<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce\Helper;

class StateValidationHelper
{
    private static $supportedCountriesWithStates = [
        'United States',
        'Canada',
        'Australia',
        'Brazil',
        'China',
        'India',
        'Ireland',
        'Italy',
        'Mexico',
    ];

    /**
     * Out of the box SF only supports states for the following countries. So in order to prevent SF from rejecting the entire payload, we'll
     * only send state if it is supported out of the box by SF.
     *
     * @return array
     */
    public static function validate(array $mappedData)
    {
        if (!isset($mappedData['State'])) {
            return $mappedData;
        }

        if (
            !isset($mappedData['Country']) ||
            !in_array($mappedData['Country'], self::$supportedCountriesWithStates)
        ) {
            unset($mappedData['State']);

            return $mappedData;
        }

        return $mappedData;
    }
}
