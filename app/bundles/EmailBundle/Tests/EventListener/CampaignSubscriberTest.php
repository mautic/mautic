<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Test\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\EmailBundle\EventListener\CampaignSubscriber;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class CampaignSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    private $config = [
        'useremail' => [
            'email' => 33,
        ],
        'user_id' => [6, 7],
        'to_owner' => true,
        'to' => 'hello@there.com, bob@bobek.cz',
        'bcc' => 'hidden@translation.in',
    ];

    public function testOnCampaignTriggerActionSendEmailToUserWithWrongEventType()
    {
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEventModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMessageQueueModel = $this->getMockBuilder(MessageQueueModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockSendEmailToUser = $this->getMockBuilder(SendEmailToUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new CampaignSubscriber($mockLeadModel, $mockEmailModel, $mockEventModel, $mockMessageQueueModel, $mockSendEmailToUser);

        $args = [
            'lead'  => 64,
            'event' => [
                'type'        => 'email.send',
                'properties'  => $this->config,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];
        $event = new CampaignExecutionEvent($args, false);
        $subscriber->onCampaignTriggerActionSendEmailToUser($event);

        $this->assertFalse($event->getResult());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithSendingTheEmail()
    {
        $lead = new Lead();

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEventModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMessageQueueModel = $this->getMockBuilder(MessageQueueModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockSendEmailToUser = $this->getMockBuilder(SendEmailToUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new CampaignSubscriber($mockLeadModel, $mockEmailModel, $mockEventModel, $mockMessageQueueModel, $mockSendEmailToUser);

        $args = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'email.send.to.user',
                'properties' => $this->config,
            ],
            'eventDetails' => [
            ],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $mockSendEmailToUser->expects($this->once())
            ->method('sendEmailToUsers')
            ->with($this->config, $lead);

        $event = new CampaignExecutionEvent($args, false);

        $subscriber->onCampaignTriggerActionSendEmailToUser($event);

        $this->assertTrue($event->getResult());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithError()
    {
        $lead = new Lead();

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEventModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMessageQueueModel = $this->getMockBuilder(MessageQueueModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockSendEmailToUser = $this->getMockBuilder(SendEmailToUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new CampaignSubscriber($mockLeadModel, $mockEmailModel, $mockEventModel, $mockMessageQueueModel, $mockSendEmailToUser);

        $args = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'email.send.to.user',
                'properties' => $this->config,
            ],
            'eventDetails' => [
            ],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $mockSendEmailToUser->expects($this->once())
            ->method('sendEmailToUsers')
            ->with($this->config, $lead)
            ->will($this->throwException(new EmailCouldNotBeSentException('Something happenned')));

        $event = new CampaignExecutionEvent($args, false);

        $subscriber->onCampaignTriggerActionSendEmailToUser($event);

        $expected = [
            'failed' => 1,
            'reason' => 'Something happenned'
        ];

        $this->assertSame($expected, $event->getResult());
    }
}
