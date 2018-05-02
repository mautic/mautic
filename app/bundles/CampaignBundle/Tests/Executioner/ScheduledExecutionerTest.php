<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContacts;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Translation\Translator;
use Psr\Log\NullLogger;

class ScheduledExecutionerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LeadEventLogRepository
     */
    private $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Translator
     */
    private $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventExecutioner
     */
    private $executioner;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventScheduler
     */
    private $scheduler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ScheduledContacts
     */
    private $contactFinder;

    protected function setUp()
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

        $this->contactFinder = $this->getMockBuilder(ScheduledContacts::class)
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

        $counter = $this->getExecutioner()->execute($campaign, $limiter);

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

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter);

        $this->assertEquals(4, $counter->getTotalEvaluated());
    }

    public function testSpecificEventsAreExecuted()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
        $event->method('getId')
            ->willReturn(1);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $log1 = new LeadEventLog();
        $log1->setEvent($event);
        $log1->setCampaign($campaign);
        $log1->setDateTriggered(new \DateTime());

        $log2 = new LeadEventLog();
        $log2->setEvent($event);
        $log2->setCampaign($campaign);
        $log2->setDateTriggered(new \DateTime());

        $logs = new ArrayCollection([$log1, $log2]);

        $this->repository->expects($this->once())
            ->method('getScheduledById')
            ->with([1, 2])
            ->willReturn($logs);

        $this->scheduler->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        // Should only be executed once because the two logs were grouped by event ID
        $this->executioner->expects($this->exactly(1))
            ->method('executeLogs');

        $counter = $this->getExecutioner()->executeByIds([1, 2]);

        // Two events were evaluated
        $this->assertEquals(2, $counter->getTotalEvaluated());
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
