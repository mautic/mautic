<?php

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\Helper\InactiveHelper;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\BufferedOutput;

class InactiveExecutionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|InactiveContactFinder
     */
    private $inactiveContactFinder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Translator
     */
    private $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventScheduler
     */
    private $eventScheduler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|InactiveHelper
     */
    private $inactiveHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventExecutioner
     */
    private $eventExecutioner;

    protected function setUp(): void
    {
        $this->inactiveContactFinder = $this->getMockBuilder(InactiveContactFinder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventScheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inactiveHelper = $this->getMockBuilder(InactiveHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventExecutioner = $this->getMockBuilder(EventExecutioner::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testNoContactsFoundResultsInNothingExecuted()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('getEventsByType')
            ->willReturn(new ArrayCollection());

        $this->inactiveContactFinder->expects($this->never())
            ->method('getContactCount');

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(0, $counter->getEvaluated());
    }

    public function testNoEventsFoundResultsInNothingExecuted()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('getEventsByType')
            ->willReturn(new ArrayCollection([new Event()]));

        $this->inactiveContactFinder->expects($this->once())
            ->method('getContactCount')
            ->willReturn(0);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(0, $counter->getTotalEvaluated());
    }

    public function testNextBatchOfContactsAreExecuted()
    {
        $decision = new Event();
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('getEventsByType')
            ->willReturn(new ArrayCollection([$decision]));

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $this->inactiveContactFinder->expects($this->once())
            ->method('getContactCount')
            ->willReturn(2);

        $this->inactiveContactFinder->expects($this->exactly(3))
            ->method('getContacts')
            ->with(null, $decision, $limiter)
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([3 => new Lead()]),
                new ArrayCollection([10 => new Lead()]),
                new ArrayCollection([])
            );

        $this->inactiveHelper->expects($this->exactly(2))
            ->method('getEarliestInactiveDateTime')
            ->willReturn(new \DateTime());

        $this->eventScheduler->expects($this->exactly(2))
            ->method('getSortedExecutionDates')
            ->willReturn([]);

        $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());
    }

    public function testValidationExecutesNothingIfCampaignUnpublished()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('isPublished')
            ->willReturn(false);

        $event = new Event();
        $event->setCampaign($campaign);

        $this->inactiveHelper->expects($this->once())
            ->method('getCollectionByDecisionId')
            ->with(1)
            ->willReturn(new ArrayCollection([$event]));

        $this->inactiveContactFinder->expects($this->never())
            ->method('getContacts');

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->validate(1, $limiter, new BufferedOutput());
        $this->assertEquals(0, $counter->getTotalEvaluated());
    }

    public function testValidationEvaluatesFoundEvents()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $decision = new Event();
        $decision->setCampaign($campaign);

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $this->inactiveHelper->expects($this->once())
            ->method('getCollectionByDecisionId')
            ->with(1)
            ->willReturn(new ArrayCollection([$decision]));

        $this->inactiveContactFinder->expects($this->once())
            ->method('getContactCount')
            ->willReturn(2);

        $this->inactiveContactFinder->expects($this->exactly(3))
            ->method('getContacts')
            ->with(null, $decision, $limiter)
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([3 => new Lead()]),
                new ArrayCollection([10 => new Lead()]),
                new ArrayCollection([])
            );

        $this->inactiveHelper->expects($this->exactly(2))
            ->method('getEarliestInactiveDateTime')
            ->willReturn(new \DateTime());

        $this->eventScheduler->expects($this->exactly(2))
            ->method('getSortedExecutionDates')
            ->willReturn([]);

        $this->getExecutioner()->validate(1, $limiter, new BufferedOutput());
    }

    private function getExecutioner()
    {
        return new InactiveExecutioner(
            $this->inactiveContactFinder,
            new NullLogger(),
            $this->translator,
            $this->eventScheduler,
            $this->inactiveHelper,
            $this->eventExecutioner
        );
    }
}
