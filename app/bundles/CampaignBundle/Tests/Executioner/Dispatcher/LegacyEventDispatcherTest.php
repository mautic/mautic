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
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockBuilder;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LegacyEventDispatcherTest extends \PHPUnit\Framework\TestCase
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
     * @var MockBuilder|LeadModel
     */
    private $leadModel;

    /**
     * @var MockBuilder|NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var MockBuilder|MauticFactory
     */
    private $mauticFactory;

    protected function setUp()
    {
        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->notificationHelper = $this->getMockBuilder(NotificationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mauticFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAllEventsAreFailedWithBadConfig()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())
            ->method('getConfig')
            ->willReturn([]);

        $logs = new ArrayCollection([new LeadEventLog()]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pendingEvent->expects($this->once())
            ->method('failAll');

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, false, $pendingEvent, $this->mauticFactory);
    }

    public function testPrimayLegacyEventsAreProcessed()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn(['eventName' => 'something']);

        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);
        $leadEventLog->setLead(new Lead());
        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        // BC default is to have pass
        $pendingEvent->expects($this->once())
            ->method('pass');

        $this->leadModel->expects($this->exactly(2))
            ->method('setSystemCurrentLead');

        // Legacy custom event should dispatch
        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(CampaignExecutionEvent::class));

        // Legacy execution event should dispatch
        $this->dispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_EXECUTION, $this->isInstanceOf(CampaignExecutionEvent::class));

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, false, $pendingEvent);
    }

    public function testPrimaryCallbackIsProcessed()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn(['callback' => [self::class, 'bogusCallback']]);

        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);
        $leadEventLog->setLead(new Lead());
        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        // BC default is to have pass
        $pendingEvent->expects($this->once())
            ->method('pass');

        $this->leadModel->expects($this->exactly(2))
            ->method('setSystemCurrentLead');

        // Legacy execution event should dispatch
        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_EXECUTION, $this->isInstanceOf(CampaignExecutionEvent::class));

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, false, $pendingEvent);
    }

    public function testArrayResultAppendedToMetadata()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn(['eventName' => 'something']);

        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);
        $leadEventLog->setLead(new Lead());
        $leadEventLog->setMetadata(['bar' => 'foo']);

        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        // BC default is to have pass
        $pendingEvent->expects($this->once())
            ->method('pass');

        $this->leadModel->expects($this->exactly(2))
            ->method('setSystemCurrentLead');

        // Legacy custom event should dispatch
        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(CampaignExecutionEvent::class))
            ->willReturnCallback(function ($eventName, CampaignExecutionEvent $event) {
                $event->setResult(['foo' => 'bar']);
            });

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, false, $pendingEvent);

        $this->assertEquals(['bar' => 'foo', 'foo' => 'bar'], $leadEventLog->getMetadata());
    }

    public function testFailedResultAsFalseIsProcessed()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn(['eventName' => 'something']);

        $lead     = new Lead();
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);
        $leadEventLog->setLead($lead);
        $leadEventLog->setMetadata(['bar' => 'foo']);

        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Should fail because we're returning false
        $pendingEvent->expects($this->once())
            ->method('fail');

        $this->leadModel->expects($this->exactly(2))
            ->method('setSystemCurrentLead');

        // Legacy custom event should dispatch
        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(CampaignExecutionEvent::class))
            ->willReturnCallback(function ($eventName, CampaignExecutionEvent $event) {
                $event->setResult(false);
            });

        $this->dispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_FAILED, $this->isInstanceOf(FailedEvent::class));

        $this->scheduler->expects($this->once())
            ->method('rescheduleFailures');

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with($lead, $event);

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, false, $pendingEvent);
    }

    public function testFailedResultAsArrayIsProcessed()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn(['eventName' => 'something']);

        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);
        $leadEventLog->setLead(new Lead());

        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Should fail because we're returning false
        $pendingEvent->expects($this->once())
            ->method('fail');

        $this->leadModel->expects($this->exactly(2))
            ->method('setSystemCurrentLead');

        // Legacy custom event should dispatch
        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(CampaignExecutionEvent::class))
            ->willReturnCallback(function ($eventName, CampaignExecutionEvent $event) {
                $event->setResult(['result' => false, 'foo' => 'bar']);
            });

        $this->dispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_FAILED, $this->isInstanceOf(FailedEvent::class));

        $this->scheduler->expects($this->once())
            ->method('rescheduleFailures');

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, false, $pendingEvent);
    }

    public function testPassWithErrorIsHandled()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn(['eventName' => 'something']);

        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);
        $leadEventLog->setLead(new Lead());
        $leadEventLog->setMetadata(['bar' => 'foo']);

        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Should pass but with an error logged
        $pendingEvent->expects($this->once())
            ->method('passWithError');

        $this->leadModel->expects($this->exactly(2))
            ->method('setSystemCurrentLead');

        // Legacy custom event should dispatch
        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(CampaignExecutionEvent::class))
            ->willReturnCallback(function ($eventName, CampaignExecutionEvent $event) {
                $event->setResult(['failed' => 1, 'reason' => 'because']);
            });

        $this->scheduler->expects($this->never())
            ->method('rescheduleFailure');

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, false, $pendingEvent);
    }

    public function testLogIsPassed()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn(['eventName' => 'something']);

        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);
        $leadEventLog->setLead(new Lead());
        $leadEventLog->setMetadata(['bar' => 'foo']);

        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Should fail because we're returning false
        $pendingEvent->expects($this->once())
            ->method('pass');

        $this->leadModel->expects($this->exactly(2))
            ->method('setSystemCurrentLead');

        // Should pass
        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(CampaignExecutionEvent::class))
            ->willReturnCallback(function ($eventName, CampaignExecutionEvent $event) {
                $event->setResult(true);
            });

        $this->scheduler->expects($this->never())
            ->method('rescheduleFailure');

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, false, $pendingEvent);
    }

    public function testLegacyEventDispatchedForConvertedBatchActions()
    {
        $config = $this->getMockBuilder(AbstractEventAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(1))
            ->method('getConfig')
            ->willReturn(['eventName' => 'something']);

        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);
        $leadEventLog->setLead(new Lead());
        $leadEventLog->setMetadata(['bar' => 'foo']);

        $logs = new ArrayCollection([$leadEventLog]);

        $pendingEvent = $this->getMockBuilder(PendingEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Should never be called
        $pendingEvent->expects($this->never())
            ->method('pass');

        $this->leadModel->expects($this->exactly(2))
            ->method('setSystemCurrentLead');

        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('something', $this->isInstanceOf(CampaignExecutionEvent::class))
            ->willReturnCallback(function ($eventName, CampaignExecutionEvent $event) {
                $event->setResult(true);
            });

        $this->getLegacyEventDispatcher()->dispatchCustomEvent($config, $logs, true, $pendingEvent);
    }

    /**
     * @return LegacyEventDispatcher
     */
    private function getLegacyEventDispatcher()
    {
        return new LegacyEventDispatcher(
            $this->dispatcher,
            $this->scheduler,
            new NullLogger(),
            $this->leadModel,
            $this->notificationHelper,
            $this->mauticFactory
        );
    }

    public static function bogusCallback()
    {
        return true;
    }
}
