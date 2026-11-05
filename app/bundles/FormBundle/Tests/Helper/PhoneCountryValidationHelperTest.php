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
        $names = array_keys($countries);
        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);
        $this->assertArrayHasKey('United States', $countries);
        $this->assertSame('US', $countries['United States']);
    }

    public function testValidatesPhoneNumberForCountry(): void
    {
        $this->assertTrue(PhoneCountryValidationHelper::isValidForCountry('+12025550123', 'US'));
        $this->assertFalse(PhoneCountryValidationHelper::isValidForCountry('+5511999999999', 'US'));
        $this->assertFalse(PhoneCountryValidationHelper::isValidForCountry('not a phone number', 'US'));
    }

    public function testRejectsTextThatMerelyContainsPhoneNumber(): void
    {
        $this->assertFalse(PhoneCountryValidationHelper::isValidForCountry('Call me at +12025550123', 'US'));
        $this->assertFalse(PhoneCountryValidationHelper::isValidForCountry('+12025550123 ext', 'US'));
    }

    public function testRejectsVanityNumberLetters(): void
    {
        $this->assertFalse(PhoneCountryValidationHelper::isValidForCountry('+1800FLOWERS', 'US'));
    }

    public function testAcceptsFormattingSeparators(): void
    {
        $this->assertTrue(PhoneCountryValidationHelper::isValidForCountry('030/901820', 'DE'));
    }
}
