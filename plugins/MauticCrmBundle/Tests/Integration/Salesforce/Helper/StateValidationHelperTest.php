<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Integration\Salesforce\Helper;

use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Helper\StateValidationHelper;
use PHPUnit\Framework\TestCase;

final class StateValidationHelperTest extends TestCase
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
