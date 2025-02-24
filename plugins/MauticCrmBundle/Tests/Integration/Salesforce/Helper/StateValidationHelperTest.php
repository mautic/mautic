<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration\Salesforce\Helper;

use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Helper\StateValidationHelper;

class StateValidationHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testStateIsRemovedWhenCountryIsUnknown(): void
    {
        $payload = [
            'State' => 'Paris',
        ];

        $this->assertEquals([], StateValidationHelper::validate($payload));
    }

    public function testStateIsRemovedWhenCountryIsNotSupported(): void
    {
        $payload = [
            'Country' => 'France',
            'State'   => 'Paris',
        ];

        $this->assertEquals(['Country' => 'France'], StateValidationHelper::validate($payload));
    }

    public function testStateIsLeftWhenCountryIsSupported(): void
    {
        $payload = [
            'Country' => 'United States',
            'State'   => 'Texas',
        ];

        $this->assertEquals($payload, StateValidationHelper::validate($payload));
    }
}
