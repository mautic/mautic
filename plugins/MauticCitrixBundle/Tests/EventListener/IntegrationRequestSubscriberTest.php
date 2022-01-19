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
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\MauticCitrixBundle\EventListener\IntegrationRequestSubscriber;
use PHPUnit\Framework\TestCase;

class IntegrationRequestSubscriberTest extends TestCase
{
    /** @var PluginIntegrationRequestEvent */
    protected $event;

    /** @var IntegrationRequestSubscriber */
    protected $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = $this->getMockBuilder(IntegrationRequestSubscriber::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getParameters', 'getAuthorization'])
            ->getMock();

        $integration = $this->getMockBuilder(UnifiedIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = new PluginIntegrationRequestEvent($integration, '\'oauth/v2/token\'', null, null, null, null, null);
    }

    public function testGetSubscribedEventsMethod()
    {
        $this->assertSame(IntegrationRequestSubscriber::getSubscribedEvents(), [
            PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST => [
                'getParameters',
                0,
            ],
        ]);
    }

    public function testExceptionOnEmptyClientId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No client ID given.');

        $this->event->setParameters([
            'client_secret' => 'abc',
        ]);

        $this->subscriber->getParameters($this->event);
    }

    public function testExceptionOnEmptyClientSecret()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No client secret given.');

        $this->event->setParameters([
            'client_id' => 'abc',
        ]);

        $this->subscriber->getParameters($this->event);
    }

    public function testExceptionOnEmptyParameters()
    {
        $this->expectException(\Exception::class);

        $this->event->setParameters([]);

        $this->subscriber->getParameters($this->event);
    }

    public function testNoExceptionOnCorrectParameters()
    {
        $this->event->setParameters([
            'client_id'     => 'abc',
            'client_secret' => 'def',
        ]);

        $this->subscriber->getParameters($this->event);
        $this->addToAssertionCount(1);
    }

    public function testHeaders()
    {
        $this->event->setParameters([
            'client_id'     => 'abc',
            'client_secret' => 'def',
        ]);

        $this->subscriber->getParameters($this->event);

        $this->assertSame($this->event->getHeaders(), [
            'Authorization' => 'Basic YWJjOmRlZg==',
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ]);
    }
}
