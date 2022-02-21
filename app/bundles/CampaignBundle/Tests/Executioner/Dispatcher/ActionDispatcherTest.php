<?php

declare(strict_types=1);

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
use Mautic\CampaignBundle\Entity\Campaign;
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
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ActionDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|EventScheduler
     */
    private $scheduler;

    /**
     * @var MockObject|LegacyEventDispatcher
     */
    private $legacyDispatcher;

    /**
     * @var MockObject|NotificationHelper
     */
    private $notificationHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher         = $this->createMock(EventDispatcherInterface::class);
        $this->scheduler          = $this->createMock(EventScheduler::class);
        $this->notificationHelper = $this->createMock(NotificationHelper::class);
        $this->legacyDispatcher   = $this->createMock(LegacyEventDispatcher::class);
    }

    public function testActionBatchEventIsDispatchedWithSuccessAndFailedLogs(): void
    {
        $event = new Event();
        $event->setCampaign(new Campaign());

        $lead1 = new Lead();
        $lead1->setId(1);

        $lead2 = new Lead();
        $lead2->setId(2);

        $log1 = new LeadEventLog();
        $log1->setLead($lead1);
        $log1->setEvent($event);

        $log2 = new LeadEventLog();
        $log2->setLead($lead2);
        $log2->setEvent($event);

        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $config = $this->createMock(ActionAccessor::class);
        $config->expects($this->once())
            ->method('getBatchEventName')
            ->willReturn('something');

        $dispatcCounter = 0;

        $this->dispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [],
                [CampaignEvents::ON_EVENT_EXECUTED, $this->isInstanceOf(ExecutedEvent::class)],
                [CampaignEvents::ON_EVENT_EXECUTED_BATCH, $this->isInstanceOf(ExecutedBatchEvent::class)],
                [CampaignEvents::ON_EVENT_FAILED, $this->isInstanceOf(FailedEvent::class)]
            )
            ->willReturnCallback(
                function (string $eventName, $event) use ($logs, &$dispatcCounter) {
                    ++$dispatcCounter;
                    if (1 === $dispatcCounter) {
                        Assert::assertInstanceOf(PendingEvent::class, $event);
                        $event->pass($logs->get(1));
                        $event->fail($logs->get(2), 'just because');
                    }
                }
            );

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

    public function testActionLogNotProcessedExceptionIsThrownIfLogNotProcessedWithSuccess(): void
    {
        $this->expectException(LogNotProcessedException::class);

        $event = new Event();
        $event->setCampaign(new Campaign());

        $lead1 = new Lead();
        $lead1->setId(1);

        $lead2 = new Lead();
        $lead2->setId(2);

        $log1 = new LeadEventLog();
        $log1->setLead($lead1);
        $log1->setEvent($event);

        $log2 = new LeadEventLog();
        $log2->setLead($lead2);
        $log2->setEvent($event);

        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $config = $this->createMock(ActionAccessor::class);

        $config->expects($this->once())
            ->method('getBatchEventName')
            ->willReturn('something');

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, PendingEvent $pendingEvent) use ($logs) {
                    $pendingEvent->pass($logs->get(1));

                    // One log is not processed so the exception should be thrown
                }
            );

        $this->getEventDispatcher()->dispatchEvent($config, $event, $logs);
    }

    public function testActionLogNotProcessedExceptionIsThrownIfLogNotProcessedWithFailed(): void
    {
        $this->expectException(LogNotProcessedException::class);

        $event = new Event();
        $event->setCampaign(new Campaign());

        $lead1 = new Lead();
        $lead1->setId(1);

        $lead2 = new Lead();
        $lead2->setId(2);

        $log1 = new LeadEventLog();
        $log1->setLead($lead1);
        $log1->setEvent($event);

        $log2 = new LeadEventLog();
        $log2->setLead($lead2);
        $log2->setEvent($event);

        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $config = $this->createMock(ActionAccessor::class);

        $config->expects($this->once())
            ->method('getBatchEventName')
            ->willReturn('something');

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, PendingEvent $pendingEvent) use ($logs) {
                    $pendingEvent->fail($logs->get(2), 'something');

                    // One log is not processed so the exception should be thrown
                }
            );

        $this->getEventDispatcher()->dispatchEvent($config, $event, $logs);
    }

    public function testActionBatchEventIsIgnoredWithLegacy(): void
    {
        $event  = new Event();
        $config = $this->createMock(ActionAccessor::class);

        $config->expects($this->once())
            ->method('getBatchEventName')
            ->willReturn(null);

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->legacyDispatcher->expects($this->once())
            ->method('dispatchCustomEvent');

        $this->getEventDispatcher()->dispatchEvent($config, $event, new ArrayCollection());
    }

    private function getEventDispatcher(): ActionDispatcher
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
