<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Helper;

use Mautic\FormBundle\Helper\PhoneCountryValidationHelper;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PhoneCountryValidationHelper::class)]
final class PhoneCountryValidationHelperTest extends TestCase
{
    public function testGetsCountriesKeyedByNameValuedByRegionCode(): void
    {
        $countries = PhoneCountryValidationHelper::getCountries();

        self::assertGreaterThan(50, count($countries));
        self::assertArrayHasKey('United States', $countries);
        self::assertSame('US', $countries['United States']);
    }

    public function testValidatesPhoneNumberForCountry(): void
    {
        self::assertTrue(PhoneCountryValidationHelper::isValidForCountry('+12025550123', 'US'));
        self::assertFalse(PhoneCountryValidationHelper::isValidForCountry('+5511999999999', 'US'));
        self::assertFalse(PhoneCountryValidationHelper::isValidForCountry('not a phone number', 'US'));
    }
}
