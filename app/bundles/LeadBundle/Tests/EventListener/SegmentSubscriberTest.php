<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListEvent as SegmentEvent;
use Mautic\LeadBundle\EventListener\SegmentSubscriber;
use Mautic\LeadBundle\LeadEvents;

class SegmentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $ipLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $auditLogModel = $this->getMockBuilder(AuditLogModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new SegmentSubscriber($ipLookupHelper, $auditLogModel);

        $this->assertEquals(
            [
                LeadEvents::LIST_POST_SAVE   => ['onSegmentPostSave', 0],
                LeadEvents::LIST_POST_DELETE => ['onSegmentDelete', 0],
            ],
            $subscriber->getSubscribedEvents()
        );
    }

    public function testOnSegmentPostSave()
    {
        $this->onSegmentPostSaveMethodCall(false); // update segment log
        $this->onSegmentPostSaveMethodCall(true); // create segment log
    }

    public function testOnSegmentDelete()
    {
        $segmentId        = 1;
        $segmentName      = 'name';
        $ip               = '127.0.0.2';

        $log = [
            'bundle'    => 'lead',
            'object'    => 'segment',
            'objectId'  => $segmentId,
            'action'    => 'delete',
            'details'   => ['name', $segmentName],
            'ipAddress' => $ip,
        ];

        $ipLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->will($this->returnValue($ip));

        $auditLogModel = $this->getMockBuilder(AuditLogModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $subscriber = new SegmentSubscriber($ipLookupHelper, $auditLogModel);

        $segment = $this->getMockBuilder(LeadList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $segment->deletedId = $segmentId;

        $segment->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($segmentName));

        $event = $this->getMockBuilder(SegmentEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

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
    private function onSegmentPostSaveMethodCall($isNew)
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

        $ipLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->will($this->returnValue($ip));

        $auditLogModel = $this->getMockBuilder(AuditLogModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $subscriber = new SegmentSubscriber($ipLookupHelper, $auditLogModel);

        $segment = $this->getMockBuilder(LeadList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $segment->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($segmentId));

        $event = $this->getMockBuilder(SegmentEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

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
