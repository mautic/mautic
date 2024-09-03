<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListEvent as SegmentEvent;
use Mautic\LeadBundle\EventListener\SegmentSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSubscriberTest extends TestCase
{
    /**
     * @var IpLookupHelper&MockObject
     */
    private MockObject $ipLookupHelper;

    /**
     * @var AuditLogModel&MockObject
     */
    private MockObject $auditLogModel;

    /**
     * @var ListModel&MockObject
     */
    private MockObject $listModel;

    /**
     * @var TranslatorInterface&MockObject
     */
    private MockObject $translator;

    /**
     * @var SegmentUsedInCampaignsValidator&MockObject
     */
    private MockObject $segmentUsedInCampaignsValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->ipLookupHelper                  = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel                   = $this->createMock(AuditLogModel::class);
        $this->listModel                       = $this->createMock(ListModel::class);
        $this->segmentUsedInCampaignsValidator = $this->createMock(SegmentUsedInCampaignsValidator::class);
        $this->translator                      = $this->createMock(TranslatorInterface::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $subscriber  = new SegmentSubscriber($this->ipLookupHelper, $this->auditLogModel, $this->listModel, $this->segmentUsedInCampaignsValidator, $this->translator);

        $this->assertEquals(
            [
                LeadEvents::LIST_POST_SAVE     => ['onSegmentPostSave', 0],
                LeadEvents::LIST_POST_DELETE   => ['onSegmentDelete', 0],
                LeadEvents::LIST_PRE_UNPUBLISH => [
                    ['validateSegmentFilters', 0],
                    ['validateSegmentsUsedInCampaigns', 0],
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

    public function testOnSegmentDelete(): void
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
            ->will($this->returnValue($ip));

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $subscriber  = new SegmentSubscriber($this->ipLookupHelper, $this->auditLogModel, $this->listModel, $this->segmentUsedInCampaignsValidator, $this->translator);

        $segment            = $this->createMock(LeadList::class);
        $segment->deletedId = $segmentId;
        $segment->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($segmentName));

        $event = $this->createMock(SegmentEvent::class);
        $event->expects($this->once())
            ->method('getList')
            ->will($this->returnValue($segment));

        $subscriber->onSegmentDelete($event);
    }

    /**
     * Test create or update segment logging.
     *
     * @param bool $isNew
     */
    private function onSegmentPostSaveMethodCall($isNew): void
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
            ->will($this->returnValue($ip));

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $subscriber  = new SegmentSubscriber($ipLookupHelper, $auditLogModel, $this->listModel, $this->segmentUsedInCampaignsValidator, $this->translator);

        $segment = $this->createMock(LeadList::class);
        $segment->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($segmentId));

        $event = $this->createMock(SegmentEvent::class);
        $event->expects($this->once())
            ->method('getList')
            ->will($this->returnValue($segment));
        $event->expects($this->once())
            ->method('getChanges')
            ->will($this->returnValue($changes));
        $event->expects($this->once())
            ->method('isNew')
            ->will($this->returnValue($isNew));

        $subscriber->onSegmentPostSave($event);
    }
}
