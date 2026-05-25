<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Helper;

use Mautic\FormBundle\Helper\PhoneCountryValidationHelper;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PhoneCountryValidationHelper::class)]
final class PhoneCountryValidationHelperTest extends TestCase
{
    public function testGetsCountriesIndexedByCountryName(): void
    {
        $countries = PhoneCountryValidationHelper::getCountries();

        self::assertArrayHasKey('United States', $countries);
        self::assertSame('United States', $countries['United States']);
    }

    public function testGetsCountryCodeFromName(): void
    {
        self::assertSame('US', PhoneCountryValidationHelper::getCountryCodeFromName('United States'));
    }

    public function testReturnsNullForUnknownCountryName(): void
    {
        self::assertNull(PhoneCountryValidationHelper::getCountryCodeFromName('Atlantis'));
    }

    public function testValidatesPhoneNumberForCountry(): void
    {
        self::assertTrue(PhoneCountryValidationHelper::isValidForCountry('+12025550123', 'US'));
        self::assertFalse(PhoneCountryValidationHelper::isValidForCountry('+5511999999999', 'US'));
        self::assertFalse(PhoneCountryValidationHelper::isValidForCountry('not a phone number', 'US'));
    }
}
