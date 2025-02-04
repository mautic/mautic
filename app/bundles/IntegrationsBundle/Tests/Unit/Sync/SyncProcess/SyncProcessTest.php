<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncProcess;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Event\CompletedSyncIterationEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectMappingsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\Helper\RelationsHelper;
use Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Notifier;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\IntegrationSyncProcess;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\MauticSyncProcess;
use Mautic\IntegrationsBundle\Sync\SyncProcess\SyncProcess;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SyncProcessTest extends TestCase
{
    /**
     * @var MockObject|MappingManualDAO
     */
    private MockObject $mappingManualDAO;

    /**
     * @var MockObject|MauticSyncDataExchange
     */
    private MockObject $internalSyncDataExchange;

    /**
     * @var MockObject|SyncDataExchangeInterface
     */
    private MockObject $integrationSyncDataExchange;

    /**
     * @var MockObject|SyncDateHelper
     */
    private MockObject $syncDateHelper;

    /**
     * @var MockObject|MappingHelper
     */
    private MockObject $mappingHelper;

    /**
     * @var MockObject|RelationsHelper
     */
    private MockObject $relationsHelper;

    /**
     * @var MockObject|IntegrationSyncProcess
     */
    private MockObject $integrationSyncProcess;

    /**
     * @var MockObject|MauticSyncProcess
     */
    private MockObject $mauticSyncProcess;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $eventDispatcher;

    /**
     * @var MockObject|Notifier
     */
    private MockObject $notifier;

    /**
     * @var MockObject|InputOptionsDAO
     */
    private MockObject $inputOptionsDAO;

    /**
     * @var MockObject|SyncServiceInterface
     */
    private MockObject $syncService;

    private SyncProcess $syncProcess;

    protected function setUp(): void
    {
        $this->syncDateHelper              = $this->createMock(SyncDateHelper::class);
        $this->mappingHelper               = $this->createMock(MappingHelper::class);
        $this->relationsHelper             = $this->createMock(RelationsHelper::class);
        $this->integrationSyncProcess      = $this->createMock(IntegrationSyncProcess::class);
        $this->mauticSyncProcess           = $this->createMock(MauticSyncProcess::class);
        $this->eventDispatcher             = $this->createMock(EventDispatcherInterface::class);
        $this->notifier                    = $this->createMock(Notifier::class);
        $this->mappingManualDAO            = $this->createMock(MappingManualDAO::class);
        $this->integrationSyncDataExchange = $this->createMock(SyncDataExchangeInterface::class);
        $this->internalSyncDataExchange    = $this->createMock(MauticSyncDataExchange::class);
        $this->inputOptionsDAO             = $this->createMock(InputOptionsDAO::class);
        $this->syncService                 = $this->createMock(SyncServiceInterface::class);

        $this->syncProcess = new SyncProcess(
            $this->syncDateHelper,
            $this->mappingHelper,
            $this->relationsHelper,
            $this->integrationSyncProcess,
            $this->mauticSyncProcess,
            $this->eventDispatcher,
            $this->notifier,
            $this->mappingManualDAO,
            $this->internalSyncDataExchange,
            $this->integrationSyncDataExchange,
            $this->inputOptionsDAO,
            $this->syncService
        );
    }

    public function testBatchSyncEventsAreDispatched(): void
    {
        $this->inputOptionsDAO->expects($this->once())
            ->method('pullIsEnabled')
            ->willReturn(true);

        $this->inputOptionsDAO->expects($this->once())
            ->method('pushIsEnabled')
            ->willReturn(true);

        $this->syncDateHelper->expects($this->once())
            ->method('setInternalSyncStartDateTime');

        // Integration to Mautic

        // fetch the report from the integration
        $integrationSyncReport = $this->createMock(ReportDAO::class);
        $integrationSyncReport->expects($this->exactly(2))
            ->method('shouldSync')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->integrationSyncProcess->expects($this->exactly(2))
            ->method('getSyncReport')
            ->withConsecutive([1], [2])
            ->willReturn($integrationSyncReport);

        // generate the order based on the report
        $integrationSyncOrder = $this->createMock(OrderDAO::class);
        $integrationSyncOrder->expects($this->once())
            ->method('shouldSync')
            ->willReturn(true);
        $this->mauticSyncProcess->expects($this->once())
            ->method('getSyncOrder')
            ->with($integrationSyncReport)
            ->willReturn($integrationSyncOrder);
        $integrationSyncOrder->expects($this->once())
            ->method('getDeletedObjects')
            ->willReturn([new ObjectChangeDAO('foobar', 'foo', 'foo1', 'contact', 1)]);
        $integrationSyncOrder->expects($this->once())
            ->method('getRemappedObjects')
            ->willReturn([new RemappedObjectDAO('foobar', 'foo', 'foo1', 'bar', 'bar1')]);

        // execute the order
        $objectMappings = $this->createMock(ObjectMappingsDAO::class);
        $objectMappings->expects($this->once())
            ->method('getNewMappings')
            ->willReturn([(new ObjectMapping())->setIntegrationObjectName('foo')]);
        $objectMappings->expects($this->once())
            ->method('getUpdatedMappings')
            ->willReturn([(new ObjectMapping())->setIntegrationObjectName('bar')]);
        $this->internalSyncDataExchange->expects($this->once())
            ->method('executeSyncOrder')
            ->willReturn($objectMappings);

        $this->eventDispatcher
            ->method('dispatch')
            ->withConsecutive(
                [
                    // the integration to mautic batch event should be dispatched
                    $this->callback(function (CompletedSyncIterationEvent $event) {
                        $orderResult = $event->getOrderResults();
                        Assert::assertCount(1, $orderResult->getUpdatedObjectMappings('bar'));
                        Assert::assertCount(1, $orderResult->getNewObjectMappings('foo'));
                        Assert::assertCount(1, $orderResult->getDeletedObjects('foo'));
                        Assert::assertCount(1, $orderResult->getRemappedObjects('bar'));

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_BATCH_SYNC_COMPLETED_INTEGRATION_TO_MAUTIC,
                ],
                [
                    // the integration to mautic batch event should be dispatched
                    $this->callback(function (CompletedSyncIterationEvent $event) {
                        $orderResult = $event->getOrderResults();
                        Assert::assertCount(1, $orderResult->getNewObjectMappings('bar'));
                        Assert::assertCount(1, $orderResult->getUpdatedObjectMappings('foo'));

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_BATCH_SYNC_COMPLETED_MAUTIC_TO_INTEGRATION,
                ]
            );

        // Mautic to integration

        // fetch the report from Mautic
        $internalSyncReport = $this->createMock(ReportDAO::class);
        $internalSyncReport->expects($this->exactly(2))
            ->method('shouldSync')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->mauticSyncProcess->expects($this->exactly(2))
            ->method('getSyncReport')
            ->withConsecutive([1], [2])
            ->willReturn($internalSyncReport);

        // generate the order based on the report
        $internalSyncOrder = $this->createMock(OrderDAO::class);
        $internalSyncOrder->expects($this->once())
            ->method('shouldSync')
            ->willReturnOnConsecutiveCalls(true);
        $internalSyncOrder->expects($this->exactly(2))
            ->method('getObjectMappings')
            ->willReturn([(new ObjectMapping())->setIntegrationObjectName('bar')]);
        $updatedObjectMapping = new UpdatedObjectMappingDAO('foobar', 'foo', 'foo1', new \DateTime());
        $updatedObjectMapping->setObjectMapping((new ObjectMapping())->setIntegrationObjectName('foo'));

        // Test that getOrderResultsForInternalSync ignores an object with a missing ObjectMapping
        $updatedObjectMapping2 = new UpdatedObjectMappingDAO('foobar', 'foo', 'foo2', new \DateTime());

        $internalSyncOrder->expects($this->exactly(2))
            ->method('getUpdatedObjectMappings')
            ->willReturn([$updatedObjectMapping, $updatedObjectMapping2]);
        $internalSyncOrder->expects($this->exactly(2))
            ->method('getDeletedObjects')
            ->willReturn([]); // currently not supported for Mautic to integration
        $internalSyncOrder->expects($this->exactly(2))
            ->method('getRemappedObjects')
            ->willReturn([]); // currently not supported for Mautic to integration
        $internalSyncOrder->expects($this->once())
            ->method('getNotifications')
            ->willReturn([]);
        $internalSyncOrder->expects($this->once())
            ->method('getSuccessfullySyncedObjects')
            ->willReturn([]);

        $this->integrationSyncProcess->expects($this->once())
            ->method('getSyncOrder')
            ->with($internalSyncReport)
            ->willReturn($internalSyncOrder);

        // execute the order
        $this->internalSyncDataExchange->expects($this->once())
            ->method('executeSyncOrder')
            ->willReturn($objectMappings);

        $this->syncProcess->execute();
    }
}
