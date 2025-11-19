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
     * Returns list of available countries for form choices.
     * Uses libphonenumber's supported regions and maps them to country names.
     *
     * @return array<string, string>
     */
    public static function getCountries(): array
    {
        $phoneUtil    = PhoneNumberUtil::getInstance();
        $regions      = $phoneUtil->getSupportedRegions();
        $allCountries = Countries::getNames();
        $result       = [];

        foreach ($regions as $regionCode) {
            if (isset($allCountries[$regionCode])) {
                $countryName          = $allCountries[$regionCode];
                $result[$countryName] = $countryName;
            }
        }

        return $result;
    }

    /**
     * Gets the country code (region code) from a country name.
     *
     * @return string|null The country code if found, null otherwise
     */
    public static function getCountryCodeFromName(string $countryName): ?string
    {
        $phoneUtil    = PhoneNumberUtil::getInstance();
        $regions      = $phoneUtil->getSupportedRegions();
        $allCountries = Countries::getNames();

        foreach ($regions as $regionCode) {
            if (isset($allCountries[$regionCode]) && $allCountries[$regionCode] === $countryName) {
                return $regionCode;
            }
        }

        return null;
    }
}
