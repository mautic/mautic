<?php

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\EmailBundle\EventListener\CampaignSubscriber;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Translation\TranslatorInterface;

class CampaignSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $config = [
        'useremail' => [
            'email' => 0,
        ],
        'user_id'  => [6, 7],
        'to_owner' => true,
        'to'       => 'hello@there.com, bob@bobek.cz',
        'bcc'      => 'hidden@translation.in',
    ];

    /**
     * @var EmailModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $emailModel;

    /**
     * @var RealTimeExecutioner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $realTimeExecutioner;

    /**
     * @var SendEmailToUser|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sendEmailToUser;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var CampaignSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailModel          = $this->createMock(EmailModel::class);
        $this->realTimeExecutioner = $this->createMock(RealTimeExecutioner::class);
        $this->sendEmailToUser     = $this->createMock(SendEmailToUser::class);
        $this->translator          = $this->createMock(TranslatorInterface::class);

        $this->subscriber = new CampaignSubscriber(
            $this->emailModel,
            $this->realTimeExecutioner,
            $this->sendEmailToUser,
            $this->translator
        );
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithWrongEventType()
    {
        $eventAccessor = $this->createMock(ActionAccessor::class);
        $event         = new Event();
        $lead          = (new Lead())->setEmail('tester@mautic.org');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog
            ->method('getLead')
            ->willReturn($lead);
        $leadEventLog
            ->method('getId')
            ->willReturn(6);

        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = new PendingEvent($eventAccessor, $event, $logs);
        $this->subscriber->onCampaignTriggerActionSendEmailToUser($pendingEvent);

        $this->assertCount(0, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithSendingTheEmail()
    {
        $eventAccessor = $this->createMock(ActionAccessor::class);
        $event         = (new Event())->setType('email.send.to.user');
        $lead          = (new Lead())->setEmail('tester@mautic.org');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog
            ->method('getLead')
            ->willReturn($lead);
        $leadEventLog
            ->method('getId')
            ->willReturn(0);
        $leadEventLog
            ->method('setIsScheduled')
            ->with(false)
            ->willReturn($leadEventLog);

        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = new PendingEvent($eventAccessor, $event, $logs);
        $this->subscriber->onCampaignTriggerActionSendEmailToUser($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithError()
    {
        $eventAccessor = $this->createMock(ActionAccessor::class);
        $event         = (new Event())->setType('email.send.to.user');
        $lead          = (new Lead())->setEmail('tester@mautic.org');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog
            ->method('getLead')
            ->willReturn($lead);
        $leadEventLog
            ->method('getId')
            ->willReturn(0);
        $leadEventLog
            ->method('setIsScheduled')
            ->with(false)
            ->willReturn($leadEventLog);
        $leadEventLog
            ->method('getMetadata')
            ->willReturn([]);

        $logs = new ArrayCollection([$leadEventLog]);

        $this->sendEmailToUser->expects($this->once())
            ->method('sendEmailToUsers')
            ->with([], $lead)
            ->will($this->throwException(new EmailCouldNotBeSentException('Something happened')));

        $pendingEvent = new PendingEvent($eventAccessor, $event, $logs);
        $this->subscriber->onCampaignTriggerActionSendEmailToUser($pendingEvent);

        $this->assertCount(0, $pendingEvent->getSuccessful());

        $failures = $pendingEvent->getFailures();
        $this->assertCount(1, $failures);
        /** @var LeadEventLog $failure */
        $failure    = $failures->first();
        $failedLead = $failure->getLead();

        $this->assertSame('tester@mautic.org', $failedLead->getEmail());
    }
}
