<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Tests\EventListener;

use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\MauticCitrixBundle\EventListener\IntegrationRequestSubscriber;
use PHPUnit_Framework_TestCase;

class IntegrationRequestSubscriberTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests getSubscribedEvents method.
     */
    public function testGetSubscribedEventsMethod()
    {
        $this->assertSame(IntegrationRequestSubscriber::getSubscribedEvents(), [
            PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST => [
                'getParameters',
                0,
            ],
        ]);
    }

    public function testGetParametersMethod()
    {
        $event = $this->getMockBuilder(PluginIntegrationRequestEvent::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setHeaders'])
            ->getMock();

        $event
            ->method('getUrl')
            ->willReturn('\'oauth/v2/token\'');

        $event
            ->method('getParameters')
            ->will($this->onConsecutiveCalls([], [], [], []));

        $subscriber = $this->getMockBuilder(IntegrationRequestSubscriber::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getParameters', 'getAuthorization'])
            ->getMock();
    }
}
