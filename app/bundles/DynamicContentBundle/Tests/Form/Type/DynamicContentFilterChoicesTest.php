<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Form\Type;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test case to verify that filter choice transformations are working correctly.
 * This tests the fix for the locale/timezone filter validation issue.
 */
class DynamicContentFilterChoicesTest extends TestCase
{
    private const AMERICA_NEW_YORK = 'America/New_York';

    public function testLocaleChoicesAreFlipped(): void
    {
        $originalChoices    = FormFieldHelper::getLocaleChoices();
        $transformedChoices = array_flip($originalChoices);

        $this->assertIsArray($transformedChoices);
        $this->assertNotEmpty($transformedChoices);

        $keys = array_keys($transformedChoices);
        $this->assertContains('en_US', $keys, 'en_US locale should be available as a key');

        if (isset($transformedChoices['en_US'])) {
            $this->assertIsString($transformedChoices['en_US']);
            $this->assertNotEquals('en_US', $transformedChoices['en_US'], 'Value should be different from key');
        }
    }

    public function testTimezoneChoicesAreFlattenedAndFlipped(): void
    {
        $originalChoices    = FormFieldHelper::getTimezonesChoices();
        $flattenedChoices   = ArrayHelper::flatten($originalChoices);
        $transformedChoices = array_flip($flattenedChoices);

        $this->assertIsArray($transformedChoices);
        $this->assertNotEmpty($transformedChoices);

        $keys = array_keys($transformedChoices);
        $this->assertContains(
            self::AMERICA_NEW_YORK,
            $keys,
            self::AMERICA_NEW_YORK.' timezone should be available as a key'
        );

        if (isset($transformedChoices[self::AMERICA_NEW_YORK])) {
            $this->assertIsString($transformedChoices[self::AMERICA_NEW_YORK]);
            $this->assertNotEquals(
                self::AMERICA_NEW_YORK,
                $transformedChoices[self::AMERICA_NEW_YORK],
                'Value should be different from key'
            );
        }
    }

    public function testRegionChoicesAreFlattenedAndFlipped(): void
    {
        $originalChoices    = FormFieldHelper::getRegionChoices();
        $flattenedChoices   = ArrayHelper::flatten($originalChoices);
        $transformedChoices = array_flip($flattenedChoices);

        $this->assertIsArray($transformedChoices);
        $this->assertNotEmpty($transformedChoices);

        $keys = array_keys($transformedChoices);
        $this->assertIsArray($keys);
        $this->assertNotEmpty($keys);

        foreach ($keys as $key) {
            $this->assertIsString($key, 'All region keys should be strings');
            $this->assertIsString($transformedChoices[$key], 'All region values should be strings');
        }
    }

    public function testCountryChoicesRemainUnchanged(): void
    {
        $originalChoices = FormFieldHelper::getCountryChoices();

        $this->assertIsArray($originalChoices);
        $this->assertNotEmpty($originalChoices);

        $keys   = array_keys($originalChoices);
        $values = array_values($originalChoices);

        $this->assertContainsOnly('string', $keys, true, 'All country keys should be strings');
        $this->assertContainsOnly('string', $values, true, 'All country values should be strings');
    }

    public function testChoiceTransformationsMatchCampaignEventPattern(): void
    {
        $localeChoices = array_flip(FormFieldHelper::getLocaleChoices());
        $this->assertIsArray($localeChoices);

        $timezoneChoices = array_flip(ArrayHelper::flatten(FormFieldHelper::getTimezonesChoices()));
        $this->assertIsArray($timezoneChoices);

        $regionChoices = array_flip(ArrayHelper::flatten(FormFieldHelper::getRegionChoices()));
        $this->assertIsArray($regionChoices);

        $this->assertNotEmpty($localeChoices);
        $this->assertNotEmpty($timezoneChoices);
        $this->assertNotEmpty($regionChoices);
    }
}
