<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\Executioner;

use Mautic\IntegrationsBundle\Event\InternalObjectCreateEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\OrderExecutioner;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderExecutionerTest extends TestCase
{
    private const INTEGRATION_NAME = 'Test';

    /**
     * @var MappingHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mappingHelper;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var ObjectProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectProvider;

    /**
     * @var OrderExecutioner
     */
    private $orderExecutioner;

    protected function setup(): void
    {
        $this->mappingHelper    = $this->createMock(MappingHelper::class);
        $this->dispatcher       = $this->createMock(EventDispatcherInterface::class);
        $this->objectProvider   = $this->createMock(ObjectProvider::class);
        $this->orderExecutioner = new OrderExecutioner(
            $this->mappingHelper,
            $this->dispatcher,
            $this->objectProvider
        );
    }

    public function testContactsAreUpdatedAndCreated(): void
    {
        $this->objectProvider->expects($this->exactly(2))
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn(new Contact());

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        $this->assertSame(Contact::NAME, $event->getObject()->getName());
                        $this->assertSame([1, 2], $event->getIdentifiedObjectIds());
                        $this->assertCount(2, $event->getUpdateObjects());

                        return true;
                    }),
                ],
                [
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        $this->assertSame(Contact::NAME, $event->getObject()->getName());
                        $this->assertCount(1, $event->getCreateObjects());

                        return true;
                    }),
                ]
            );

        $this->mappingHelper->expects($this->exactly(1))
            ->method('updateObjectMappings');

        $this->mappingHelper->expects($this->exactly(1))
            ->method('saveObjectMappings');

        $this->orderExecutioner->execute($this->getSyncOrder(Contact::NAME));
    }

    public function testCompaniesAreUpdatedAndCreated(): void
    {
        $this->objectProvider->expects($this->exactly(2))
            ->method('getObjectByName')
            ->with(Company::NAME)
            ->willReturn(new Company());

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        $this->assertSame(Company::NAME, $event->getObject()->getName());
                        $this->assertSame([1, 2], $event->getIdentifiedObjectIds());
                        $this->assertCount(2, $event->getUpdateObjects());

                        return true;
                    }),
                ],
                [
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        $this->assertSame(Company::NAME, $event->getObject()->getName());
                        $this->assertCount(1, $event->getCreateObjects());

                        return true;
                    }),
                ]
            );

        $this->mappingHelper->expects($this->exactly(1))
            ->method('updateObjectMappings');

        $this->mappingHelper->expects($this->exactly(1))
            ->method('saveObjectMappings');

        $syncOrder = $this->getSyncOrder(Company::NAME);
        $this->orderExecutioner->execute($syncOrder);
    }

    public function testMixedObjectsAreUpdatedAndCreated(): void
    {
        $this->objectProvider->expects($this->exactly(4))
            ->method('getObjectByName')
            ->withConsecutive(
                [Contact::NAME],
                [Company::NAME],
                [Contact::NAME],
                [Company::NAME]
            )
            ->willReturnOnConsecutiveCalls(
                new Contact(),
                new Company(),
                new Contact(),
                new Company()
            );

        $this->dispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        $this->assertSame(Contact::NAME, $event->getObject()->getName());

                        return true;
                    }),
                ],
                [
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        $this->assertSame(Company::NAME, $event->getObject()->getName());

                        return true;
                    }),
                ],
                [
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        $this->assertSame(Contact::NAME, $event->getObject()->getName());

                        return true;
                    }),
                ],
                [
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        $this->assertSame(Company::NAME, $event->getObject()->getName());

                        return true;
                    }),
                ]
            );

        $this->mappingHelper->expects($this->exactly(2))
            ->method('updateObjectMappings');

        $this->mappingHelper->expects($this->exactly(2))
            ->method('saveObjectMappings');

        // Merge companies and contacts for the test
        $syncOrder        = $this->getSyncOrder(Contact::NAME);
        $companySyncOrder = $this->getSyncOrder(Company::NAME);
        foreach ($companySyncOrder->getChangedObjectsByObjectType(Company::NAME) as $objectChange) {
            $syncOrder->addObjectChange($objectChange);
        }

        $this->orderExecutioner->execute($syncOrder);
    }

    /**
     * @throws \Exception
     */
    private function getSyncOrder(string $objectName): OrderDAO
    {
        $syncOrder = new OrderDAO(new \DateTimeImmutable(), false, self::INTEGRATION_NAME);

        // Two updates
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, $objectName, 1, $objectName, 1));
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, $objectName, 2, $objectName, 2));

        // One create
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, $objectName, null, $objectName, 3));

        return $syncOrder;
    }
}
