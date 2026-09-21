<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListEvent as SegmentEvent;
use Mautic\LeadBundle\EventListener\SegmentSubscriber;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SegmentSubscriberTest extends TestCase
{
    /**
     * @var MockObject&IpLookupHelper
     */
    private MockObject $ipLookupHelper;

    /**
     * @var MockObject&AuditLogModel
     */
    private MockObject $auditLogModel;

    /**
     * @var MockObject&ListModel
     */
    private MockObject $listModel;

    private CoreParametersHelper&MockObject $coreParametersHelper;

    private SegmentCountCacheHelper&\PHPUnit\Framework\MockObject\Stub $segmentCountCacheHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ipLookupHelper                  = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel                   = $this->createMock(AuditLogModel::class);
        $this->listModel                       = $this->createMock(ListModel::class);
        $this->coreParametersHelper            = $this->createMock(CoreParametersHelper::class);
        $this->segmentCountCacheHelper         = $this->createStub(SegmentCountCacheHelper::class);
        $this->coreParametersHelper->method('get')->willReturnCallback(fn (): false => false);
    }

    public function testGetSubscribedEvents(): void
    {
        $subscriber  = new SegmentSubscriber(
            $this->ipLookupHelper,
            $this->auditLogModel,
            $this->listModel,
            $this->createStub(SegmentUsedInCampaignsValidator::class),
            $this->coreParametersHelper,
            $this->segmentCountCacheHelper,
            $this->createStub(TranslatorInterface::class)
        );

        $this->assertSame(
            [
                LeadEvents::LIST_POST_SAVE     => ['onSegmentPostSave', 0],
                LeadEvents::ON_LIST_DELETE     => ['onSegmentDelete', 0],
                LeadEvents::LIST_POST_DELETE   => [
                    ['onSegmentPostDelete', 0],
                    ['clearSegmentCountCache', 0],
                ],
                LeadEvents::LIST_PRE_DELETE   => [
                    ['onSegmentPreDelete', 0],
                ],
                LeadEvents::LIST_PRE_UNPUBLISH => [
                    ['onSegmentPreUnpublish', 0],
                ],
            ],
            $subscriber->getSubscribedEvents()
        );
    }

    public function testOnSegmentPostSave(): void
    {
        $this->onSegmentPostSaveMethodCall(false); // update segment log
        $this->onSegmentPostSaveMethodCall(true); // create segment log
    }

    public function testOnSegmentPostDelete(): void
    {
        $segmentId        = 1;
        $segmentName      = 'name';
        $ip               = '127.0.0.2';
        $log              = [
            'bundle'    => 'lead',
            'object'    => 'segment',
            'objectId'  => $segmentId,
            'action'    => 'delete',
            'details'   => ['name', $segmentName],
            'ipAddress' => $ip,
        ];

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn($ip);

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $subscriber  = new SegmentSubscriber(
            $this->ipLookupHelper,
            $this->auditLogModel,
            $this->listModel,
            $this->createStub(SegmentUsedInCampaignsValidator::class),
            $this->coreParametersHelper,
            $this->segmentCountCacheHelper,
            $this->createStub(TranslatorInterface::class)
        );

        $segment            = $this->createMock(LeadList::class);
        $segment->deletedId = $segmentId;
        $segment->expects($this->once())
            ->method('getName')
            ->willReturn($segmentName);

        $event = $this->createMock(SegmentEvent::class);
        $event->expects($this->once())
            ->method('getList')
            ->willReturn($segment);

        $subscriber->onSegmentPostDelete($event);
    }

    public function testOnSegmentDelete(): void
    {
        $segmentId = 1;

        $this->listModel->expects($this->once())
            ->method('removeLeadsByListId')
            ->with($segmentId);

        $this->listModel->expects($this->once())
            ->method('hardDeleteEntity');

        $subscriber = new SegmentSubscriber(
            $this->ipLookupHelper,
            $this->auditLogModel,
            $this->listModel,
            $this->createStub(SegmentUsedInCampaignsValidator::class),
            $this->coreParametersHelper,
            $this->segmentCountCacheHelper,
            $this->createStub(TranslatorInterface::class)
        );

        $segment            = $this->createMock(LeadList::class);
        $segment->deletedId = $segmentId;

        $segment->expects($this->once())
            ->method('getId')
            ->willReturn($segmentId);

        $event = $this->createMock(SegmentEvent::class);
        $event->expects($this->once())
            ->method('getList')
            ->willReturn($segment);

        $subscriber->onSegmentDelete($event);
    }

    /**
     * Test create or update segment logging.
     */
    private function onSegmentPostSaveMethodCall(bool $isNew): void
    {
        $segmentId = 1;
        $changes   = ['changes'];
        $ip        = '127.0.0.2';

        $log = [
            'bundle'    => 'lead',
            'object'    => 'segment',
            'objectId'  => $segmentId,
            'action'    => ($isNew) ? 'create' : 'update',
            'details'   => $changes,
            'ipAddress' => $ip,
        ];

        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn($ip);

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $subscriber  = new SegmentSubscriber(
            $ipLookupHelper,
            $auditLogModel,
            $this->listModel,
            $this->createStub(SegmentUsedInCampaignsValidator::class),
            $this->coreParametersHelper,
            $this->segmentCountCacheHelper,
            $this->createStub(TranslatorInterface::class)
        );

        $segment = $this->createMock(LeadList::class);
        $segment->expects($this->once())
            ->method('getId')
            ->willReturn($segmentId);

        $event = $this->createMock(SegmentEvent::class);
        $event->expects($this->once())
            ->method('getList')
            ->willReturn($segment);
        $event->expects($this->once())
            ->method('getChanges')
            ->willReturn($changes);
        $event->expects($this->once())
            ->method('isNew')
            ->willReturn($isNew);

        $subscriber->onSegmentPostSave($event);
    }
}
