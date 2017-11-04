<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests;

use MauticPlugin\MauticCrmBundle\Tests\Stubs\StubIntegration;

class CrmAbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testFieldMatchingPriority()
    {
        $config = [
            'update_mautic' => [
                'email'      => '1',
                'first_name' => '0',
                'last_name'  => '0',
                'address_1'  => '1',
                'address_2'  => '1',
            ],
        ];

        /** @var \PHPUnit_Framework_MockObject_MockBuilder $mockBuilder */
        $mockBuilder = $this->getMockBuilder(StubIntegration::class);
        $mockBuilder->disableOriginalConstructor();

        /** @var StubIntegration $integration */
        $integration = $mockBuilder->getMock();

        $methodMautic = new \ReflectionMethod(StubIntegration::class, 'getPriorityFieldsForMautic');
        $methodMautic->setAccessible(true);

        $methodIntegration = new \ReflectionMethod(StubIntegration::class, 'getPriorityFieldsForIntegration');
        $methodIntegration->setAccessible(true);

        $fieldsForMautic = $methodMautic->invokeArgs($integration, [$config]);

        $this->assertSame(
            ['email', 'address_1', 'address_2'],
            $fieldsForMautic,
            'Fields to update in Mautic should return fields marked as 1 in the integration priority config.'
        );

        $fieldsForIntegration = $methodIntegration->invokeArgs($integration, [$config]);

        $this->assertSame(
            ['first_name', 'last_name'],
            $fieldsForIntegration,
            'Fields to update in the integration should return fields marked as 0 in the integration priority config.'
        );
    }
}
