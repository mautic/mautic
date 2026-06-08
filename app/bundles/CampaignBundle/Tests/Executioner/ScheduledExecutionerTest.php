<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\Helper\EventRedirectionHelper;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\BufferedOutput;

class ScheduledExecutionerTest extends TestCase
{
    private MockObject&LeadEventLogRepository $repository;

    private MockObject&Translator $translator;

    private MockObject&EventExecutioner $executioner;

    private MockObject&EventScheduler $scheduler;

    private MockObject&ScheduledContactFinder $contactFinder;

    private MockObject&ProcessSignalService $processSignalService;

    private MockObject&EventRedirectionHelper $redirectionHelper;

    private MockObject&EntityManagerInterface $entityManager;

    private MockObject&LeadRepository $leadRepository;

    protected function setUp(): void
    {
        $this->repository           = $this->createMock(LeadEventLogRepository::class);
        $this->translator           = $this->createMock(Translator::class);
        $this->executioner          = $this->createMock(EventExecutioner::class);
        $this->scheduler            = $this->createMock(EventScheduler::class);
        $this->contactFinder        = $this->createMock(ScheduledContactFinder::class);
        $this->processSignalService = $this->createMock(ProcessSignalService::class);
        $this->redirectionHelper    = $this->createMock(EventRedirectionHelper::class);
        $this->entityManager        = $this->createMock(EntityManagerInterface::class);
        $this->leadRepository       = $this->createMock(LeadRepository::class);

        // Configure the redirection helper mock to return the event it receives
        $this->redirectionHelper->method('handleEventRedirection')
            ->willReturnCallback(fn (Event $event) => $event);
    }

    public function testNoEventsResultInEmptyResults(): void
    {
        $this->repository->expects($this->once())
            ->method('getScheduledCounts')
            ->willReturn([]);

        $this->repository->expects($this->never())
            ->method('getScheduled');

        $campaign = $this->createMock(Campaign::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(0, $counter->getTotalEvaluated());
    }

    public function testSpecificEventsAreExecuted(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('isPublished')
            ->willReturn(true);

        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $lead = $this->createMock(Lead::class);
        $lead->method('getId')
            ->willReturn(1);

        $log1 = $this->createMock(LeadEventLog::class);
        $log1->method('getId')
            ->willReturn(1);
        $log1->method('getEvent')
            ->willReturn($event);
        $log1->method('getCampaign')
            ->willReturn($campaign);
        $log1->method('getLead')
            ->willReturn($lead);
        $log1->method('getDateTriggered')
            ->willReturn(new \DateTime());
        $log1->method('getRotation')
            ->willReturn(9);

        $log2 = $this->createMock(LeadEventLog::class);
        $log2->method('getId')
            ->willReturn(2);
        $log2->method('getEvent')
            ->willReturn($event);
        $log2->method('getCampaign')
            ->willReturn($campaign);
        $log2->method('getLead')
            ->willReturn($lead);
        $log2->method('getDateTriggered')
            ->willReturn(new \DateTime());
        $log2->method('getRotation')
            ->willReturn(10);

        $logs = new ArrayCollection([1 => $log1, 2 => $log2]);

        $this->repository->expects($this->once())
            ->method('getScheduledByIds')
            ->with([1, 2])
            ->willReturn($logs);

        $this->redirectionHelper
            ->method('handleEventRedirection')
            ->willReturn($event);

        $this->scheduler->method('validateExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->scheduler->method('shouldSchedule')
            ->willReturn(false);

        // Should only be executed once because the two logs were grouped by event ID
        $this->executioner->expects($this->exactly(1))
            ->method('executeLogs');

        $this->contactFinder->expects($this->once())
            ->method('hydrateContacts');

        $counter = $this->getExecutioner()->executeByIds([1, 2]);

        // Two events were evaluated
        $this->assertEquals(2, $counter->getTotalEvaluated());
    }

    public function testSpecificEventsWithUnpublishedCampaign(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->expects($this->once())
            ->method('isPublished')
            ->willReturn(false);

        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $lead = $this->createMock(Lead::class);
        $lead->method('getId')
            ->willReturn(3);

        $log1 = $this->createMock(LeadEventLog::class);
        $log1->method('getId')
            ->willReturn(1);
        $log1->method('getEvent')
            ->willReturn($event);
        $log1->method('getCampaign')
            ->willReturn($campaign);
        $log1->method('getLead')
            ->willReturn($lead);
        $log1->method('getDateTriggered')
            ->willReturn(new \DateTime());
        $log1->method('getRotation')
            ->willReturn(15);

        $log2 = $this->createMock(LeadEventLog::class);
        $log2->method('getId')
            ->willReturn(2);
        $log2->method('getEvent')
            ->willReturn($event);
        $log2->method('getCampaign')
            ->willReturn($campaign);
        $log2->method('getLead')
            ->willReturn($lead);
        $log2->method('getDateTriggered')
            ->willReturn(new \DateTime());
        $log2->method('getRotation')
            ->willReturn(16);

        $logs = new ArrayCollection([1 => $log1, 2 => $log2]);

        $this->repository->expects($this->once())
            ->method('getScheduledByIds')
            ->with([1, 2])
            ->willReturn($logs);

        $this->redirectionHelper
            ->method('handleEventRedirection')
            ->willReturn($event);

        $this->scheduler->method('validateExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->scheduler->method('shouldSchedule')
            ->willReturn(false);

        $this->executioner->expects($this->never())
            ->method('executeLogs');

        $this->contactFinder->expects($this->never())
            ->method('hydrateContacts');

        $counter = $this->getExecutioner()->executeByIds([1, 2]);

        // Two events were evaluated but not executed because campaign was unpublished
        $this->assertEquals(2, $counter->getTotalEvaluated());
        $this->assertEquals(0, $counter->getTotalExecuted());
    }

    private function getExecutioner(): ScheduledExecutioner
    {
        return new ScheduledExecutioner(
            $this->repository,
            new NullLogger(),
            $this->translator,
            $this->executioner,
            $this->scheduler,
            $this->contactFinder,
            $this->processSignalService,
            $this->entityManager,
            $this->redirectionHelper,
            $this->leadRepository
        );
    }
}
