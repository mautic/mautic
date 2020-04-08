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
use Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\BufferedOutput;

class KickoffExecutionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|KickoffContactFinder
     */
    private $kickoffContactFinder;

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

    protected function setUp()
    {
        $this->kickoffContactFinder = $this->getMockBuilder(KickoffContactFinder::class)
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
    }

    public function testNoContactsResultInEmptyResults()
    {
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('getRootEvents')
            ->willReturn(new ArrayCollection());

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(0, $counter->getTotalEvaluated());
    }

    public function testEventsAreScheduledAndExecuted()
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
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('getRootEvents')
            ->willReturn(new ArrayCollection([$event, $event2]));
        $event->setCampaign($campaign);
        $event2->setCampaign($campaign);

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $this->scheduler->expects($this->at(0))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->scheduler->expects($this->at(1))
            ->method('validateAndScheduleEventForContacts')
            ->willReturn(null);

        $this->scheduler->expects($this->at(2))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->scheduler->expects($this->at(3))
            ->method('validateAndScheduleEventForContacts')
            ->willReturn(null);

        $this->scheduler->expects($this->at(4))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->scheduler->expects($this->at(5))
            ->method('validateAndScheduleEventForContacts')
            ->willReturnCallback(function () {
                throw new NotSchedulableException();
            });

        $this->scheduler->expects($this->at(6))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->scheduler->expects($this->at(7))
            ->method('validateAndScheduleEventForContacts')
            ->willReturnCallback(function () {
                throw new NotSchedulableException();
            });

        $this->executioner->expects($this->exactly(1))
            ->method('executeEventsForContacts')
            ->withConsecutive(
                [
                    $this->countOf(2),
                    $this->isInstanceOf(ArrayCollection::class),
                    $this->isInstanceOf(Counter::class),
                ],
                [
                    $this->countOf(1),
                        $this->isInstanceOf(ArrayCollection::class),
                        $this->isInstanceOf(Counter::class),
                ]
            );

        $counter = $this->getExecutioner()->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(4, $counter->getTotalEvaluated());
        $this->assertEquals(2, $counter->getTotalScheduled());
    }

    /**
     * @return KickoffExecutioner
     */
    private function getExecutioner()
    {
        return new KickoffExecutioner(
            new NullLogger(),
            $this->kickoffContactFinder,
            $this->translator,
            $this->executioner,
            $this->scheduler
        );
    }
}
