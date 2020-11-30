<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\Dispatcher;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\Event\DecisionResultsEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher;
use Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DecisionDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockBuilder|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockBuilder|LegacyEventDispatcher
     */
    private $legacyDispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->legacyDispatcher = $this->getMockBuilder(LegacyEventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testDecisionEventIsDispatched()
    {
        $config = $this->getMockBuilder(DecisionAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())
            ->method('getEventName')
            ->willReturn('something');

        $this->legacyDispatcher->expects($this->never())
            ->method('dispatchDecisionEvent');

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(DecisionEvent::class));

        $this->getEventDispatcher()->dispatchRealTimeEvent($config, new LeadEventLog(), null);
    }

    public function testDecisionEvaluationEventIsDispatched()
    {
        $config = $this->getMockBuilder(DecisionAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->never())
            ->method('getEventName');

        $this->legacyDispatcher->expects($this->once())
            ->method('dispatchDecisionEvent');

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_DECISION_EVALUATION, $this->isInstanceOf(DecisionEvent::class));

        $this->getEventDispatcher()->dispatchEvaluationEvent($config, new LeadEventLog());
    }

    public function testDecisionResultsEventIsDispatched()
    {
        $config = $this->getMockBuilder(DecisionAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_DECISION_EVALUATION_RESULTS, $this->isInstanceOf(DecisionResultsEvent::class));

        $this->getEventDispatcher()->dispatchDecisionResultsEvent($config, new ArrayCollection([new LeadEventLog()]), new EvaluatedContacts());
    }

    /**
     * @return DecisionDispatcher
     */
    public function getEventDispatcher()
    {
        return new DecisionDispatcher($this->dispatcher, $this->legacyDispatcher);
    }
}
