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
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ExecutedBatchEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException;
use Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockBuilder;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ActionDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockBuilder|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockBuilder|EventScheduler
     */
    private $scheduler;

    /**
     * @var MockBuilder|LegacyEventDispatcher
     */
    private $legacyDispatcher;

    /**
     * @var MockBuilder|NotificationHelper
     */
    private $notificationHelper;

    protected function setUp(): void
    {
        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->notificationHelper = $this->getMockBuilder(NotificationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->legacyDispatcher = $this->getMockBuilder(LegacyEventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testActionBatchEventIsDispatchedWithSuccessAndFailedLogs()
    {
        $event = new Event();

        $lead1 = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead1->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $lead2 = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead2->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(2);

        $log1 = $this->getMockBuilder(LeadEventLog::class)
            ->getMock();
        $log1->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn($lead1);
        $log1->method('setIsScheduled')
            ->willReturn($log1);
        $log1->method('getEvent')
            ->willReturn($event);

        $log2 = $this->getMockBuilder(LeadEventLog::class)
            ->getMock();
        $log2->expects($this->exactly(3))
            ->method('getLead')
            ->willReturn($lead2);
        $log2->method('getMetadata')
            ->willReturn([]);
        $log2->method('getEvent')
            ->willReturn($event);

        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $config = $this->getMockBuilder(ActionAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())
            ->method('getBatchEventName')
            ->willReturn('something');

        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, PendingEvent $pendingEvent) use ($logs) {
                    $pendingEvent->pass($logs->get(1));
                    $pendingEvent->fail($logs->get(2), 'just because');
                }
            );

        $this->dispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_EXECUTED, $this->isInstanceOf(ExecutedEvent::class));

        $this->dispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_EXECUTED_BATCH, $this->isInstanceOf(ExecutedBatchEvent::class));

        $this->dispatcher->expects($this->at(3))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_FAILED, $this->isInstanceOf(FailedEvent::class));

        $this->scheduler->expects($this->once())
            ->method('rescheduleFailures')
            ->willReturnCallback(
                function (ArrayCollection $logs) use ($log2) {
                    if ($logs->count() > 1) {
                        $this->fail('Only one log was supposed to fail');
                    }

                    $this->assertEquals($log2, $logs->first());
                }
            );

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with($lead2, $event);

        $this->legacyDispatcher->expects($this->once())
            ->method('dispatchExecutionEvents');

        $this->getEventDispatcher()->dispatchEvent($config, $event, $logs);
    }

    public function testActionLogNotProcessedExceptionIsThrownIfLogNotProcessedWithSuccess()
    {
        $this->expectException(LogNotProcessedException::class);

        $event = new Event();

        $lead1 = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead1->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $lead2 = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead2->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $log1 = $this->getMockBuilder(LeadEventLog::class)
            ->getMock();
        $log1->expects($this->once())
            ->method('getLead')
            ->willReturn($lead1);
        $log1->method('setIsScheduled')
            ->willReturn($log1);
        $log1->method('getEvent')
            ->willReturn($event);

        $log2 = $this->getMockBuilder(LeadEventLog::class)
            ->getMock();
        $log2->expects($this->once())
            ->method('getLead')
            ->willReturn($lead2);
        $log2->method('getMetadata')
            ->willReturn([]);
        $log2->method('getEvent')
            ->willReturn($event);

        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $config = $this->getMockBuilder(ActionAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())
            ->method('getBatchEventName')
            ->willReturn('something');

        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, PendingEvent $pendingEvent) use ($logs) {
                    $pendingEvent->pass($logs->get(1));

                    // One log is not processed so the exception should be thrown
                }
            );

        $this->getEventDispatcher()->dispatchEvent($config, $event, $logs);
    }

    public function testActionLogNotProcessedExceptionIsThrownIfLogNotProcessedWithFailed()
    {
        $this->expectException(LogNotProcessedException::class);

        $event = new Event();

        $lead1 = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead1->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $lead2 = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead2->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $log1 = $this->getMockBuilder(LeadEventLog::class)
            ->getMock();
        $log1->expects($this->once())
            ->method('getLead')
            ->willReturn($lead1);
        $log1->method('setIsScheduled')
            ->willReturn($log1);
        $log1->method('getEvent')
            ->willReturn($event);

        $log2 = $this->getMockBuilder(LeadEventLog::class)
            ->getMock();
        $log2->expects($this->once())
            ->method('getLead')
            ->willReturn($lead2);
        $log2->method('getMetadata')
            ->willReturn([]);
        $log2->method('getEvent')
            ->willReturn($event);

        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $config = $this->getMockBuilder(ActionAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())
            ->method('getBatchEventName')
            ->willReturn('something');

        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, PendingEvent $pendingEvent) use ($logs) {
                    $pendingEvent->fail($logs->get(2), 'something');

                    // One log is not processed so the exception should be thrown
                }
            );

        $this->getEventDispatcher()->dispatchEvent($config, $event, $logs);
    }

    public function testActionBatchEventIsIgnoredWithLegacy()
    {
        $event = new Event();

        $config = $this->getMockBuilder(ActionAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())
            ->method('getBatchEventName')
            ->willReturn(null);

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->legacyDispatcher->expects($this->once())
            ->method('dispatchCustomEvent');

        $this->getEventDispatcher()->dispatchEvent($config, $event, new ArrayCollection());
    }

    /**
     * @return ActionDispatcher
     */
    private function getEventDispatcher()
    {
        return new ActionDispatcher(
            $this->dispatcher,
            new NullLogger(),
            $this->scheduler,
            $this->notificationHelper,
            $this->legacyDispatcher
        );
    }
}
