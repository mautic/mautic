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

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ConditionEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher;
use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConditionDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockBuilder|EventDispatcherInterface
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConditionEventIsDispatched()
    {
        $config = $this->getMockBuilder(ConditionAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())
            ->method('getEventName')
            ->willReturn('something');

        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(ConditionEvent::class));

        $this->dispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_CONDITION_EVALUATION, $this->isInstanceOf(ConditionEvent::class));

        $this->getEventDispatcher()->dispatchEvent($config, new LeadEventLog());
    }

    /**
     * @return ConditionDispatcher
     */
    private function getEventDispatcher()
    {
        return new ConditionDispatcher($this->dispatcher);
    }
}
