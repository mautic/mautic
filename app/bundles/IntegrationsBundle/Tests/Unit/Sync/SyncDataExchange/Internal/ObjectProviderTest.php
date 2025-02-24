<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal;

use Mautic\IntegrationsBundle\Event\InternalObjectEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ObjectProviderTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $dispatcher;

    private ObjectProvider $objectProvider;

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
                $this->isInstanceOf(InternalObjectEvent::class),
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS
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
                $this->callback(function (InternalObjectEvent $e) use ($contact) {
                    // Fake a subscriber.
                    $e->addObject($contact);

                    return true;
                }),
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS
            );

        $this->assertSame($contact, $this->objectProvider->getObjectByName(Contact::NAME));
    }

    public function testGetObjectByEntityNameIfItDoesNotExist(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(InternalObjectEvent::class),
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS,
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
                $this->callback(function (InternalObjectEvent $e) use ($contact) {
                    // Fake a subscriber.
                    $e->addObject($contact);

                    return true;
                }),
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS
            );

        $this->assertSame($contact, $this->objectProvider->getObjectByEntityName(Lead::class));
    }
}
