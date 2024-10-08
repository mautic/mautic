<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\Notification\Helper;

use Mautic\IntegrationsBundle\Event\InternalObjectOwnerEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\OwnerProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OwnerProviderTest extends TestCase
{
    /**
     * @var ObjectProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $objectProvider;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $dispatcher;

    private OwnerProvider $ownerProvider;

    protected function setUp(): void
    {
        $this->objectProvider = $this->createMock(ObjectProvider::class);
        $this->dispatcher     = $this->createMock(EventDispatcherInterface::class);
        $this->ownerProvider  = new OwnerProvider($this->dispatcher, $this->objectProvider);
    }

    public function testGetOwnersForObjectIdsWithoutIds(): void
    {
        $this->objectProvider->expects($this->never())
            ->method('getObjectByName');

        $this->assertSame([], $this->ownerProvider->getOwnersForObjectIds(Contact::NAME, []));
    }

    public function testGetOwnersForObjectIdsWithUnknownObject(): void
    {
        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with('Unicorn')
            ->willThrowException(new ObjectNotFoundException('Unicorn'));

        $this->expectException(ObjectNotSupportedException::class);

        $this->ownerProvider->getOwnersForObjectIds('Unicorn', [123]);
    }

    public function testGetOwnersForObjectIdsWithKnownObject(): void
    {
        $internalObject = new Contact();
        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function (InternalObjectOwnerEvent $event) use ($internalObject) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame([123], $event->getObjectIds());

                    // Simulate a subscriber. Format: [object_id => owner_id].
                    $event->setOwners([$event->getObjectIds()[0] => 456]);

                    return true;
                }),
                IntegrationEvents::INTEGRATION_FIND_OWNER_IDS
            );

        $this->assertSame([123 => 456], $this->ownerProvider->getOwnersForObjectIds(Contact::NAME, [123]));
    }
}
