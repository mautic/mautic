<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class KickoffExecutionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|KickoffContactFinder
     */
    private MockObject $kickoffContactFinder;

    /**
     * @var MockObject|Translator
     */
    private MockObject $translator;

    /**
     * @var MockObject|EventExecutioner
     */
    private MockObject $executioner;

    /**
     * @var MockObject|EventScheduler
     */
    private MockObject $scheduler;

    /**
     * @var CoreParametersHelper&MockObject
     */
    private MockObject $coreParametersHelper;

    protected function setUp(): void
    {
        $this->kickoffContactFinder = $this->createMock(KickoffContactFinder::class);
        $this->translator           = $this->createMock(Translator::class);
        $this->executioner          = $this->createMock(EventExecutioner::class);
        $this->scheduler            = $this->createMock(EventScheduler::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
    }

    public function testNoContactsResultInEmptyResults(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->expects($this->once())
            ->method('getRootEvents')
            ->willReturn(new ArrayCollection());

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(0, $counter->getTotalEvaluated());
    }

    public function testEventsAreScheduledAndExecuted(): void
    {
        $this->kickoffContactFinder->expects($this->once())
            ->method('getContactCount')
            ->willReturn(2);

        $this->kickoffContactFinder->expects($this->exactly(3))
            ->method('getContacts')
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([3 => new Lead()]),
                new ArrayCollection([10 => new Lead()]),
                new ArrayCollection([])
            );

        $event    = new Event();
        $event2   = new Event();
        $campaign = new class extends Campaign {
            /**
             * @var ArrayCollection<int,Event>
             */
            public ArrayCollection $rootEvents;

            /**
             * @return ArrayCollection<int,Event>
             */
            public function getRootEvents(): ArrayCollection
            {
                return $this->rootEvents;
            }
        };
        $campaign->rootEvents = new ArrayCollection([$event, $event2]);
        $event->setCampaign($campaign);
        $event2->setCampaign($campaign);

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $this->scheduler->expects($this->exactly(4))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        $callbackCounter = 0;
        $this->scheduler->expects($this->exactly(4))
            ->method('validateAndScheduleEventForContacts')
            ->willReturnCallback(
                function () use (&$callbackCounter): void {
                    ++$callbackCounter;
                    if (in_array($callbackCounter, [3, 4])) {
                        throw new NotSchedulableException();
                    }
                }
            );

        $this->executioner->expects($this->exactly(1))
            ->method('executeEventsForContacts')->willReturnCallback(function (...$parameters) {
                $this->assertCount(2, $parameters[0]);
                $this->assertInstanceOf(ArrayCollection::class, $parameters[1]);
                $this->assertInstanceOf(Counter::class, $parameters[2]);
            });

        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(4, $counter->getTotalEvaluated());
        $this->assertEquals(2, $counter->getTotalScheduled());
    }

    private function getExecutioner(): KickoffExecutioner
    {
        return new KickoffExecutioner(
            new NullLogger(),
            $this->kickoffContactFinder,
            $this->translator,
            $this->executioner,
            $this->scheduler,
            $this->createMock(ProcessSignalService::class),
            $this->coreParametersHelper,
            $this->createMock(EventDispatcherInterface::class),
        );
    }
}
