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

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\EmailBundle\EventListener\CampaignSubscriber;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Translation\TranslatorInterface;

class CampaignSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $config = [
        'useremail' => [
            'email' => 33,
        ],
        'user_id'  => [6, 7],
        'to_owner' => true,
        'to'       => 'hello@there.com, bob@bobek.cz',
        'bcc'      => 'hidden@translation.in',
    ];

    /**
     * @var EmailModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $emailModel;

    /**
     * @var EventModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventModel;

    /**
     * @var SendEmailToUser|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sendEmailToUser;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var CampaignSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        parent::setUp();

        $this->emailModel      = $this->createMock(EmailModel::class);
        $this->eventModel      = $this->createMock(EventModel::class);
        $this->sendEmailToUser = $this->createMock(SendEmailToUser::class);
        $this->translator      = $this->createMock(TranslatorInterface::class);

        $this->subscriber = new CampaignSubscriber(
            $this->emailModel,
            $this->eventModel,
            $this->sendEmailToUser,
            $this->translator
        );
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithWrongEventType()
    {
        $eventAccessor = $this->createMock(ActionAccessor::class);
        $event         = (new Event())->setEventType('email.send');
        $lead          = (new Lead())->setEmail('tester@mautic.org');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog
            ->method('getLead')
            ->willReturn($lead);
        $leadEventLog
            ->method('getId')
            ->willReturn(6);

        $logs = new ArrayCollection([$leadEventLog]);

        $event = new PendingEvent($eventAccessor, $event, $logs);
        $this->subscriber->onCampaignTriggerActionSendEmailToUser($event);

        $this->assertCount(0, $event->getSuccessful());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithSendingTheEmail()
    {
        $eventAccessor = $this->createMock(ActionAccessor::class);
        $event         = (new Event())->setEventType('email.send');
        $lead          = (new Lead())->setEmail('tester@mautic.org');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog
            ->method('getLead')
            ->willReturn($lead);
        $leadEventLog
            ->method('getId')
            ->willReturn(6);

        $logs = new ArrayCollection([$leadEventLog]);

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

        $this->sendEmailToUser->expects($this->once())
            ->method('sendEmailToUsers')
            ->with($this->config, $lead);

        $event = new PendingEvent($eventAccessor, $event, $logs);
        $this->subscriber->onCampaignTriggerActionSendEmailToUser($event);

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

        $mockTranslator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new CampaignSubscriber($mockLeadModel, $mockEmailModel, $mockEventModel, $mockMessageQueueModel, $mockSendEmailToUser, $mockTranslator);

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
            'reason' => 'Something happenned',
        ];

        $this->assertSame($expected, $event->getResult());
    }
}
