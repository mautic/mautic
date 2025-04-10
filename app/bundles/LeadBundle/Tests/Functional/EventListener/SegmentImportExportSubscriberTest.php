<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\EventListener\SegmentImportExportSubscriber;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PluginBundle\Model\PluginModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SegmentImportExportSubscriberTest extends TestCase
{
    private SegmentImportExportSubscriber $subscriber;
    private MockObject&ListModel $leadListModel;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&PluginModel $pluginModel;
    private MockObject&EventDispatcherInterface $dispatcher;
    private MockObject&FieldModel $fieldModel;
    private MockObject&IpLookupHelper $ipLookupHelper;
    private MockObject&DenormalizerInterface $serializer;
    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->leadListModel   = $this->createMock(ListModel::class);
        $this->entityManager   = $this->createMock(EntityManagerInterface::class);
        $this->auditLogModel   = $this->createMock(AuditLogModel::class);
        $this->pluginModel     = $this->createMock(PluginModel::class);
        $this->dispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->fieldModel      = $this->createMock(FieldModel::class);
        $this->ipLookupHelper  = $this->createMock(IpLookupHelper::class);
        $this->serializer      = $this->createMock(DenormalizerInterface::class);

        $leadListRepository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $leadListRepository
            ->method('findOneBy')
            ->willReturn(null); // Simulate no existing segment (so it creates a new one)

        $this->entityManager
            ->method('getRepository')
            ->with(LeadList::class)
            ->willReturn($leadListRepository);

        $this->subscriber = new SegmentImportExportSubscriber(
            $this->leadListModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->pluginModel,
            $this->dispatcher,
            $this->fieldModel,
            $this->ipLookupHelper,
            $this->serializer
        );

        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }

    public function testSegmentExport(): void
    {
        $leadList = $this->createMock(LeadList::class);
        $leadList->method('getId')->willReturn(1);
        $leadList->method('getName')->willReturn('Test Segment');
        $leadList->method('getIsPublished')->willReturn(true);
        $leadList->method('getDescription')->willReturn('Test description');
        $leadList->method('getAlias')->willReturn('test-alias');
        $leadList->method('getPublicName')->willReturn('Public Test');
        $leadList->method('getFilters')->willReturn([]);
        $leadList->method('getIsGlobal')->willReturn(false);
        $leadList->method('getIsPreferenceCenter')->willReturn(false);

        $this->leadListModel->method('getEntity')->with(1)->willReturn($leadList);

        $event = new EntityExportEvent(LeadList::ENTITY_NAME, 1);
        $this->eventDispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(LeadList::ENTITY_NAME, $exportedData);
        $this->assertNotEmpty($exportedData[LeadList::ENTITY_NAME]);

        $exportedSegment = reset($exportedData[LeadList::ENTITY_NAME]);
        $this->assertSame(1, $exportedSegment['id']);
        $this->assertSame('Test Segment', $exportedSegment['name']);
        $this->assertTrue($exportedSegment['is_published']);
        $this->assertSame('Test description', $exportedSegment['description']);
        $this->assertSame('test-alias', $exportedSegment['alias']);
        $this->assertSame('Public Test', $exportedSegment['public_name']);
        $this->assertSame([], $exportedSegment['filters']);
        $this->assertFalse($exportedSegment['is_global']);
        $this->assertFalse($exportedSegment['is_preference_center']);
    }

    public function testSegmentImport(): void
    {
        $importData = [
            [
                'id'                   => 1,
                'name'                 => 'Imported Segment',
                'is_published'         => true,
                'description'          => 'Imported description',
                'alias'                => 'imported-alias',
                'public_name'          => 'Imported Public Name',
                'filters'              => [],
                'is_global'            => true,
                'is_preference_center' => false,
                'uuid'                 => 'uuid-456',
            ],
        ];

        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $this->entityManager
            ->method('getRepository')
            ->with(LeadList::class)
            ->willReturn($repository);

        $segment = new LeadList();
        $ref     = new \ReflectionClass($segment);
        $idProp  = $ref->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($segment, 123);

        $this->serializer
            ->method('denormalize')
            ->willReturnCallback(fn ($data, $type, $format, $context) => $context['object_to_populate']);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(LeadList::class));
        $this->entityManager->expects($this->once())->method('flush');

        $event = new EntityImportEvent(LeadList::ENTITY_NAME, $importData, 99);
        $this->eventDispatcher->dispatch($event);

        $this->assertSame(123, $event->getEntityIdMap()[1]);
    }
}
