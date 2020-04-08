<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Integration\Salesforce\Helper;

use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Helper\StateValidationHelper;

class StateValidationHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testStateIsRemovedWhenCountryIsUnknown()
    {
        $payload = [
            'State' => 'Paris',
        ];

        $this->assertEquals([], StateValidationHelper::validate($payload));
    }

    public function testStateIsRemovedWhenCountryIsNotSupported()
    {
        $payload = [
            'Country' => 'France',
            'State'   => 'Paris',
        ];

        $this->assertEquals(['Country' => 'France'], StateValidationHelper::validate($payload));
    }

    public function testStateIsLeftWhenCountryIsSupported()
    {
        $payload = [
            'Country' => 'United States',
            'State'   => 'Texas',
        ];

        $this->assertEquals($payload, StateValidationHelper::validate($payload));
    }
}
