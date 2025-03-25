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
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SegmentImportExportSubscriberTest extends TestCase
{
    private SegmentImportExportSubscriber $subscriber;
    private MockObject&ListModel $leadListModel;
    private MockObject&UserModel $userModel;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&EventDispatcherInterface $dispatcher;
    private EventDispatcher $eventDispatcher;
    private AuditLogModel $auditLogModel;
    private IpLookupHelper $ipLookupHelper;
    private MockObject&FieldModel $fieldModel;

    protected function setUp(): void
    {
        $this->leadListModel  = $this->createMock(ListModel::class);
        $this->userModel      = $this->createMock(UserModel::class);
        $this->fieldModel     = $this->createMock(FieldModel::class);
        $this->entityManager  = $this->createMock(EntityManagerInterface::class);
        $this->dispatcher     = new EventDispatcher();

        $this->subscriber = new SegmentImportExportSubscriber(
            $this->leadListModel,
            $this->userModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->dispatcher,
            $this->fieldModel,
            $this->ipLookupHelper,
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
        $this->dispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(LeadList::ENTITY_NAME, $exportedData);
        $this->assertNotEmpty($exportedData[LeadList::ENTITY_NAME]);

        $exportedSegment = $exportedData[LeadList::ENTITY_NAME][0];
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
        $user = $this->createMock(User::class);
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');

        $this->userModel->method('getEntity')->with(99)->willReturn($user);

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
                'is_preference_center' => true,
            ],
        ];

        $event = new EntityImportEvent(LeadList::ENTITY_NAME, $importData, 99);
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (LeadList $segment) {
                return 'Imported Segment' === $segment->getName()
                       && true === $segment->getIsPublished()
                       && 'Imported description' === $segment->getDescription()
                       && 'imported-alias' === $segment->getAlias()
                       && 'Imported Public Name' === $segment->getPublicName()
                       && [] === $segment->getFilters()
                       && true === $segment->getIsGlobal()
                       && true === $segment->getIsPreferenceCenter()
                       && 'John Doe' === $segment->getCreatedByUser();
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $this->dispatcher->dispatch($event);
    }
}
