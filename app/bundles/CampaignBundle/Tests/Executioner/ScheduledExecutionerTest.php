<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner;

use PHPUnit\Framework\MockObject\Stub;
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
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\BufferedOutput;

#[AllowMockObjectsWithoutExpectations]
final class ScheduledExecutionerTest extends TestCase
{
    private MockObject&LeadEventLogRepository $repository;

    private Stub&Translator $translator;

    private MockObject&EventExecutioner $executioner;

    private MockObject&EventScheduler $scheduler;

    private MockObject&ScheduledContactFinder $contactFinder;

    private Stub&ProcessSignalService $processSignalService;

    private MockObject&EventRedirectionHelper $redirectionHelper;

    private Stub&EntityManagerInterface $entityManager;

    private Stub&LeadRepository $leadRepository;

    protected function setUp(): void
    {
        $this->repository           = $this->createMock(LeadEventLogRepository::class);
        $this->translator           = $this->createStub(Translator::class);
        $this->executioner          = $this->createMock(EventExecutioner::class);
        $this->scheduler            = $this->createMock(EventScheduler::class);
        $this->contactFinder        = $this->createMock(ScheduledContactFinder::class);
        $this->processSignalService = $this->createStub(ProcessSignalService::class);
        $this->redirectionHelper    = $this->createMock(EventRedirectionHelper::class);
        $this->entityManager        = $this->createStub(EntityManagerInterface::class);
        $this->leadRepository       = $this->createStub(LeadRepository::class);

        // Configure the redirection helper mock to return the event it receives
        $this->redirectionHelper->method('handleEventRedirection')
            ->willReturnCallback(fn (Event $event): Event => $event);
    }

    public function testNoEventsResultInEmptyResults(): void
    {
        $this->repository->expects($this->once())
            ->method('getScheduledCounts')
            ->willReturn([]);

        $this->repository->expects($this->never())
            ->method('getScheduled');

        $campaign = new Campaign();

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());
        $this->assertInstanceOf(Counter::class, $counter);

        $this->assertSame(0, $counter->getTotalEvaluated());
    }

    public function testSpecificEventsAreExecuted(): void
    {
        $campaign = new Campaign();
        $campaign->setIsPublished(true);

        $event = new Event();
        ReflectionHelper::setEntityId($event, 1);
        $event->setCampaign($campaign);

        $lead = new Lead();
        $lead->setId(1);

        $log1 = new LeadEventLog();
        ReflectionHelper::setEntityId($log1, 1);
        $log1->setEvent($event);
        $log1->setCampaign($campaign);
        $log1->setLead($lead);
        $log1->setDateTriggered(new \DateTime());
        $log1->setRotation(9);

        $log2 = new LeadEventLog();
        ReflectionHelper::setEntityId($log2, 2);
        $log2->setEvent($event);
        $log2->setCampaign($campaign);
        $log2->setLead($lead);
        $log2->setDateTriggered(new \DateTime());
        $log2->setRotation(10);

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
        $this->assertInstanceOf(Counter::class, $counter);

        // Two events were evaluated
        $this->assertSame(2, $counter->getTotalEvaluated());
    }

    public function testSpecificEventsWithUnpublishedCampaign(): void
    {
        $campaign = new Campaign();
        $campaign->setIsPublished(false);

        $event = new Event();
        ReflectionHelper::setEntityId($event, 1);
        $event->setCampaign($campaign);

        $lead = new Lead();
        $lead->setId(3);

        $log1 = new LeadEventLog();
        ReflectionHelper::setEntityId($log1, 1);
        $log1->setEvent($event);
        $log1->setCampaign($campaign);
        $log1->setLead($lead);
        $log1->setDateTriggered(new \DateTime());
        $log1->setRotation(15);

        $log2 = new LeadEventLog();
        ReflectionHelper::setEntityId($log2, 2);
        $log2->setEvent($event);
        $log2->setCampaign($campaign);
        $log2->setLead($lead);
        $log2->setDateTriggered(new \DateTime());
        $log2->setRotation(16);

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
        $this->assertInstanceOf(Counter::class, $counter);

        // Two events were evaluated but not executed because campaign was unpublished
        $this->assertSame(2, $counter->getTotalEvaluated());
        $this->assertSame(0, $counter->getTotalExecuted());
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
