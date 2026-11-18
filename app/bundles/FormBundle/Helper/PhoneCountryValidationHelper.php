<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Helper;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\Countries;

final class PhoneCountryValidationHelper
{
    private const PHONE_NUMBER_FORMAT = '/^\+?[\d\s\p{Zs}().\-\x{2010}-\x{2015}\/~\[\]]+\z/u';

    /**
     * Validates a phone number for a specific country using libphonenumber.
     *
     * @return bool True if the phone number is valid for the given country, false otherwise
     */
    public static function isValidForCountry(string $phoneNumber, string $countryCode): bool
    {
        $phoneNumber = trim($phoneNumber);

        if (1 !== preg_match(self::PHONE_NUMBER_FORMAT, $phoneNumber)) {
            return false;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $number = $phoneUtil->parse($phoneNumber, $countryCode);

            return $phoneUtil->isValidNumberForRegion($number, $countryCode);
        } catch (NumberParseException) {
            return false;
        }
    }

    public static function isSupportedCountry(string $countryCode): bool
    {
        return Countries::exists($countryCode)
            && in_array($countryCode, PhoneNumberUtil::getInstance()->getSupportedRegions(), true);
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
    public static function getCountries(string $locale = 'en'): array
    {
        $allCountries     = Countries::getNames($locale);
        $supportedRegions = array_fill_keys(PhoneNumberUtil::getInstance()->getSupportedRegions(), true);
        $result           = [];

        foreach ($allCountries as $regionCode => $name) {
            if (isset($supportedRegions[$regionCode])) {
                $result[$name] = $regionCode;
            }
        }

        return $result;
    }
}
