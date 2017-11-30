<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\LeadBundle\Entity\lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\EventListener\LeadSubscriber;

class LeadSubscriberTest extends CommonMocks
{
    public function testOnLeadPostSaveWillNotProcessTheSameLeadTwice()
    {
        $lead = new Lead();

        $lead->setId(54);

        $changes = [
            'title' => [
                '0' => 'sdf',
                '1' => 'Mr.',
            ],
            'fields' => [
                'firstname' => [
                    '0' => 'Test',
                    '1' => 'John',
                ],
                'lastname' => [
                    '0' => 'test',
                    '1' => 'Doe',
                ],
                'email' => [
                    '0' => 'zrosa91@gmail.com',
                    '1' => 'john@gmail.com',
                ],
                'mobile' => [
                    '0' => '345345',
                    '1' => '555555555',
                ],
            ],
            'dateModified' => [
                '0' => '2017-08-21T15:50:57+00:00',
                '1' => '2017-08-22T08:04:31+00:00',
            ],
            'dateLastActive' => [
                '0' => '2017-08-21T15:50:57+00:00',
                '1' => '2017-08-22T08:04:31+00:00',
            ],
        ];

        $ipLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $auditLogModel = $this->getMockBuilder(AuditLogModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        // This method will be called exactly once
        // even though the onLeadPostSave was called twice for the same lead
        $auditLogModel->expects($this->once())
            ->method('writeToLog');

        $subscriber = new LeadSubscriber($ipLookupHelper, $auditLogModel);

        $leadEvent = $this->getMockBuilder(LeadEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadEvent->expects($this->exactly(2))
            ->method('getLead')
            ->will($this->returnValue($lead));

        $leadEvent->expects($this->exactly(2))
            ->method('getChanges')
            ->will($this->returnValue($changes));

        $subscriber->onLeadPostSave($leadEvent);
        $subscriber->onLeadPostSave($leadEvent);
    }
}
