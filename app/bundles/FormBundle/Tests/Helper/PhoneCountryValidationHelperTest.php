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

        $this->assertGreaterThan(50, count($countries));
        $this->assertArrayHasKey('United States', $countries);
        $this->assertSame('US', $countries['United States']);
    }

    public function testValidatesPhoneNumberForCountry(): void
    {
        $this->assertTrue(PhoneCountryValidationHelper::isValidForCountry('+12025550123', 'US'));
        $this->assertFalse(PhoneCountryValidationHelper::isValidForCountry('+5511999999999', 'US'));
        $this->assertFalse(PhoneCountryValidationHelper::isValidForCountry('not a phone number', 'US'));
    }
}
