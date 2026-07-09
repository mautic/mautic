<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\EventListener\CampaignSubscriber;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SendEmailToUser
     */
    private \PHPUnit\Framework\MockObject\MockObject $sendEmailToUser;

    private CampaignSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $emailModel                = $this->createMock(EmailModel::class);
        $realTimeExecutioner       = $this->createMock(RealTimeExecutioner::class);
        $this->sendEmailToUser     = $this->createMock(SendEmailToUser::class);
        $translator                = $this->createMock(TranslatorInterface::class);
        $leadModel                 = $this->createMock(LeadModel::class);
        $statRepository            = $this->createMock(StatRepository::class);

        $this->subscriber = new CampaignSubscriber(
            $emailModel,
            $realTimeExecutioner,
            $this->sendEmailToUser,
            $translator,
            $leadModel,
            $statRepository
        );
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithWrongEventType(): void
    {
        $eventAccessor = $this->createStub(ActionAccessor::class);
        $event         = new Event();
        $lead          = (new Lead())->setEmail('tester@mautic.org');

        $event->setType(Event::TYPE_ACTION);

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

    public function testOnCampaignTriggerActionSendEmailToUserWithSendingTheEmail(): void
    {
        $eventAccessor = $this->createStub(ActionAccessor::class);
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

    public function testOnCampaignTriggerActionSendEmailToUserWithError(): void
    {
        $eventAccessor = $this->createStub(ActionAccessor::class);
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

    /**
     * @throws \Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function testOnCampaignTriggerActionSendEmailToContactWithWrongEventType(): void
    {
        $eventAccessor = $this->createStub(ActionAccessor::class);
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
        $this->subscriber->onCampaignTriggerActionSendEmailToContact($pendingEvent);

        $this->assertCount(0, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }
}
