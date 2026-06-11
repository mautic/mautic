<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Helper;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\Countries;

final class PhoneCountryValidationHelper
{
    /**
     * Validates a phone number for a specific country using libphonenumber.
     *
     * @return bool True if the phone number is valid for the given country, false otherwise
     */
    public static function isValidForCountry(string $phoneNumber, string $countryCode): bool
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $number = $phoneUtil->parse($phoneNumber, $countryCode);

            return $phoneUtil->isValidNumberForRegion($number, $countryCode);
        } catch (NumberParseException) {
            return false;
        }
    }

    /**
     * Returns list of available countries for form choices, keyed by display
     * name and valued by the stable ISO region code.
     *
     * The region code is stored as the field value so validation never depends
     * on the locale-sensitive display name (which differs between the locale
     * the form was built in and the locale a submission is validated under).
     *
     * @return array<string, string> [localized country name => ISO region code]
     */
    public static function getCountries(): array
    {
        $phoneUtil    = PhoneNumberUtil::getInstance();
        $regions      = $phoneUtil->getSupportedRegions();
        $allCountries = Countries::getNames();
        $result       = [];

        foreach ($regions as $regionCode) {
            if (isset($allCountries[$regionCode])) {
                $result[$allCountries[$regionCode]] = $regionCode;
            }
        }

        return $result;
    }
}
