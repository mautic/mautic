<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ObjectProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var ObjectProvider
     */
    private $objectProvider;

    protected function setUp(): void
    {
        $this->dispatcher     = $this->createMock(EventDispatcherInterface::class);
        $this->objectProvider = new ObjectProvider($this->dispatcher);
    }

    public function testGetObjectByNameIfItDoesNotExist(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS,
                $this->isInstanceOf(InternalObjectEvent::class)
            );

        $this->expectException(ObjectNotFoundException::class);
        $this->objectProvider->getObjectByName('Unicorn');
    }

    public function testGetObjectByNameIfItExists(): void
    {
        $contact = new Contact();
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS,
                $this->callback(function (InternalObjectEvent $e) use ($contact) {
                    // Fake a subscriber.
                    $e->addObject($contact);

                    return true;
                })
            );

        $this->assertSame($contact, $this->objectProvider->getObjectByName(Contact::NAME));
    }

    public function testGetObjectByEntityNameIfItDoesNotExist(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS,
                $this->isInstanceOf(InternalObjectEvent::class)
            );

        $this->expectException(ObjectNotFoundException::class);
        $this->objectProvider->getObjectByEntityName('Unicorn');
    }

    public function testGetObjectByEntityNameIfItExists(): void
    {
        $contact = new Contact();
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS,
                $this->callback(function (InternalObjectEvent $e) use ($contact) {
                    // Fake a subscriber.
                    $e->addObject($contact);

                    return true;
                })
            );

        $this->assertSame($contact, $this->objectProvider->getObjectByEntityName(Lead::class));
    }
}
