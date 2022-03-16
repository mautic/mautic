<?php

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Translation\Translator;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\BufferedOutput;

class ScheduledExecutionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadEventLogRepository
     */
    private $repository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Translator
     */
    private $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventExecutioner
     */
    private $executioner;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventScheduler
     */
    private $scheduler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ScheduledContactFinder
     */
    private $contactFinder;

    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(LeadEventLogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->executioner = $this->getMockBuilder(EventExecutioner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactFinder = $this->getMockBuilder(ScheduledContactFinder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testNoEventsResultInEmptyResults()
    {
        $this->repository->expects($this->once())
            ->method('getScheduledCounts')
            ->willReturn(['nada' => 0]);

        $this->repository->expects($this->never())
            ->method('getScheduled');

        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(0, $counter->getTotalEvaluated());
    }

    public function testEventsAreExecuted()
    {
        $this->repository->expects($this->once())
            ->method('getScheduledCounts')
            ->willReturn([1 => 2, 2 => 2]);

        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();

        $event = new Event();
        $event->setCampaign($campaign);

        $log1 = new LeadEventLog();
        $log1->setEvent($event);
        $log1->setCampaign($campaign);

        $log2 = new LeadEventLog();
        $log2->setEvent($event);
        $log2->setCampaign($campaign);

        $event2 = new Event();
        $event2->setCampaign($campaign);

        $log3 = new LeadEventLog();
        $log3->setEvent($event2);
        $log3->setCampaign($campaign);

        $log4 = new LeadEventLog();
        $log4->setEvent($event2);
        $log4->setCampaign($campaign);

        $this->repository->expects($this->exactly(4))
            ->method('getScheduled')
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection(
                    [
                        $log1,
                        $log2,
                    ]
                ),
                new ArrayCollection(),
                new ArrayCollection(
                    [
                        $log3,
                        $log4,
                    ]
                ),
                new ArrayCollection()
            );

        $this->executioner->expects($this->exactly(2))
            ->method('executeLogs');

        $this->scheduler->expects($this->exactly(4))
            ->method('validateExecutionDateTime')
            ->willReturn(new \DateTime());

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(4, $counter->getTotalEvaluated());
    }

    public function testEventsAreExecutedInQuietMode()
    {
        $this->repository->expects($this->once())
            ->method('getScheduledCounts')
            ->willReturn([1 => 2, 2 => 2]);

        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();

        $event = new Event();
        $event->setCampaign($campaign);

        $log1 = new LeadEventLog();
        $log1->setEvent($event);
        $log1->setCampaign($campaign);

        $log2 = new LeadEventLog();
        $log2->setEvent($event);
        $log2->setCampaign($campaign);

        $event2 = new Event();
        $event2->setCampaign($campaign);

        $log3 = new LeadEventLog();
        $log3->setEvent($event2);
        $log3->setCampaign($campaign);

        $log4 = new LeadEventLog();
        $log4->setEvent($event2);
        $log4->setCampaign($campaign);

        $this->repository->expects($this->exactly(4))
            ->method('getScheduled')
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection(
                    [
                        $log1,
                        $log2,
                    ]
                ),
                new ArrayCollection(),
                new ArrayCollection(
                    [
                        $log3,
                        $log4,
                    ]
                ),
                new ArrayCollection()
            );

        $this->executioner->expects($this->exactly(2))
            ->method('executeLogs');

        $this->scheduler->expects($this->exactly(4))
            ->method('validateExecutionDateTime')
            ->willReturn(new \DateTime());

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter);

        $this->assertEquals(4, $counter->getTotalEvaluated());
    }

    public function testSpecificEventsAreExecuted()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->method('isPublished')
            ->willReturn(true);

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
        $event->method('getId')
            ->willReturn(1);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $log1 = $this->createMock(LeadEventLog::class);
        $log1->method('getId')
            ->willReturn(1);
        $log1->method('getEvent')
            ->willReturn($event);
        $log1->method('getCampaign')
            ->willReturn($campaign);
        $log1->method('getDateTriggered')
            ->willReturn(new \DateTime());

        $log2 = $this->createMock(LeadEventLog::class);
        $log2->method('getId')
            ->willReturn(2);
        $log2->method('getEvent')
            ->willReturn($event);
        $log2->method('getCampaign')
            ->willReturn($campaign);
        $log2->method('getDateTriggered')
            ->willReturn(new \DateTime());

        $logs = new ArrayCollection([1 => $log1, 2 => $log2]);

        $this->repository->expects($this->once())
            ->method('getScheduledByIds')
            ->with([1, 2])
            ->willReturn($logs);

        $this->scheduler->method('validateExecutionDateTime')
            ->willReturn(new \DateTime());

        // Should only be executed once because the two logs were grouped by event ID
        $this->executioner->expects($this->exactly(1))
            ->method('executeLogs');

        $this->contactFinder->expects($this->exactly(1))
            ->method('hydrateContacts')
            ->with($logs);

        $counter = $this->getExecutioner()->executeByIds([1, 2]);

        // Two events were evaluated
        $this->assertEquals(2, $counter->getTotalEvaluated());
    }

    public function testEventsAreScheduled()
    {
        $this->repository->expects($this->once())
            ->method('getScheduledCounts')
            ->willReturn([1 => 2]);

        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();

        $event = new Event();
        $event->setCampaign($campaign);

        $oneMinuteDateTime = new \DateTime('+1 minutes');
        $twoMinuteDateTime = new \DateTime('+2 minutes');

        $log1 = new LeadEventLog();
        $log1->setEvent($event);
        $log1->setCampaign($campaign);

        $log2 = new LeadEventLog();
        $log2->setEvent($event);
        $log2->setCampaign($campaign);
        $log2->setDateTriggered();

        $this->repository->expects($this->exactly(2))
            ->method('getScheduled')
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection(
                    [
                        $log1,
                        $log2,
                    ]
                ),
                new ArrayCollection()
            );

        $this->executioner->expects($this->exactly(1))
            ->method('executeLogs');

        $this->scheduler->expects($this->exactly(2))
            ->method('validateExecutionDateTime')
            ->willReturnOnConsecutiveCalls(
                $oneMinuteDateTime,
                $twoMinuteDateTime
            );

        $this->scheduler->expects($this->exactly(2))
            ->method('shouldSchedule')
            ->willReturn(true);

        $this->scheduler->expects($this->exactly(2))
            ->method('reschedule')
            ->withConsecutive(
                [$log1, $oneMinuteDateTime],
                [$log2, $twoMinuteDateTime]
            );

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter);

        $this->assertEquals(2, $counter->getTotalScheduled());
    }

    public function testSpecificEventsAreScheduled()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->method('isPublished')
            ->willReturn(true);

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
        $event->method('getId')
            ->willReturn(1);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $log1 = $this->createMock(LeadEventLog::class);
        $log1->method('getId')
            ->willReturn(1);
        $log1->method('getEvent')
            ->willReturn($event);
        $log1->method('getCampaign')
            ->willReturn($campaign);
        $log1->method('getDateTriggered')
            ->willReturn(new \DateTime());

        $log2 = $this->createMock(LeadEventLog::class);
        $log2->method('getId')
            ->willReturn(2);
        $log2->method('getEvent')
            ->willReturn($event);
        $log2->method('getCampaign')
            ->willReturn($campaign);
        $log2->method('getDateTriggered')
            ->willReturn(new \DateTime());

        $logs = new ArrayCollection([1 => $log1, 2 => $log2]);

        $this->repository->expects($this->once())
            ->method('getScheduledByIds')
            ->with([1, 2])
            ->willReturn($logs);

        $twoMinuteDateTime   = new \DateTime('+2 minutes');
        $threeMinuteDateTime = new \DateTime('+3 minutes');

        $this->scheduler->expects($this->exactly(2))
            ->method('validateExecutionDateTime')
            ->willReturnOnConsecutiveCalls(
                $twoMinuteDateTime,
                $threeMinuteDateTime
            );

        $this->scheduler->expects($this->exactly(2))
            ->method('shouldSchedule')
            ->willReturn(true);

        // Should only be executed once because the two logs were grouped by event ID
        $this->executioner->expects($this->exactly(1))
            ->method('executeLogs');

        $this->contactFinder->expects($this->once())
            ->method('hydrateContacts');

        $this->scheduler->expects($this->once())
            ->method('rescheduleLogs')
            ->with($this->isInstanceOf(ArrayCollection::class), $threeMinuteDateTime);

        $counter = $this->getExecutioner()->executeByIds([1, 2]);

        // Two events were evaluated
        $this->assertEquals(2, $counter->getTotalScheduled());
    }

    public function testSpecificEventsWithUnpublishedCamapign()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('isPublished')
            ->willReturn(false);

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
        $event->method('getId')
            ->willReturn(1);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $log1 = $this->createMock(LeadEventLog::class);
        $log1->method('getId')
            ->willReturn(1);
        $log1->method('getEvent')
            ->willReturn($event);
        $log1->method('getCampaign')
            ->willReturn($campaign);
        $log1->method('getDateTriggered')
            ->willReturn(new \DateTime());

        $log2 = $this->createMock(LeadEventLog::class);
        $log2->method('getId')
            ->willReturn(2);
        $log2->method('getEvent')
            ->willReturn($event);
        $log2->method('getCampaign')
            ->willReturn($campaign);
        $log2->method('getDateTriggered')
            ->willReturn(new \DateTime());

        $logs = new ArrayCollection([1 => $log1, 2 => $log2]);

        $this->repository->expects($this->once())
            ->method('getScheduledByIds')
            ->with([1, 2])
            ->willReturn($logs);

        $this->executioner->expects($this->never())
            ->method('executeLogs');

        $this->executioner->expects($this->once())
            ->method('recordLogsWithError');

        $this->contactFinder->expects($this->never())
            ->method('hydrateContacts');

        $this->scheduler->method('validateExecutionDateTime')
            ->willReturn(new \DateTime());

        $counter = $this->getExecutioner()->executeByIds([1, 2]);

        // Two events were evaluated
        $this->assertEquals(2, $counter->getTotalEvaluated());
        $this->assertEquals(0, $counter->getTotalExecuted());
    }

    /**
     * @return ScheduledExecutioner
     */
    private function getExecutioner()
    {
        return new ScheduledExecutioner(
            $this->repository,
            new NullLogger(),
            $this->translator,
            $this->executioner,
            $this->scheduler,
            $this->contactFinder
        );
    }
}
