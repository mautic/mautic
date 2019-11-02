<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Dispatcher;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ExecutedBatchEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ActionDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * EventDispatcher constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     * @param EventScheduler           $scheduler
     * @param NotificationHelper       $notificationHelper
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        EventScheduler $scheduler,
        NotificationHelper $notificationHelper
    ) {
        $this->dispatcher         = $dispatcher;
        $this->logger             = $logger;
        $this->scheduler          = $scheduler;
        $this->notificationHelper = $notificationHelper;
    }

    /**
     * @param ActionAccessor    $config
     * @param Event             $event
     * @param ArrayCollection   $logs
     * @param PendingEvent|null $pendingEvent
     *
     * @return PendingEvent
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     */
    public function dispatchEvent(ActionAccessor $config, Event $event, ArrayCollection $logs, PendingEvent $pendingEvent = null)
    {
        if (!$pendingEvent) {
            $pendingEvent = new PendingEvent($config, $event, $logs);
        }

        $this->dispatcher->dispatch($config->getBatchEventName(), $pendingEvent);

        $success = $pendingEvent->getSuccessful();
        $failed  = $pendingEvent->getFailures();

        $this->validateProcessedLogs($logs, $success, $failed);

        if ($success) {
            $this->dispatchExecutedEvent($config, $event, $success);
        }

        if ($failed) {
            $this->dispatchedFailedEvent($config, $failed);
        }

        return $pendingEvent;
    }

    /**
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $logs
     */
    private function dispatchExecutedEvent(AbstractEventAccessor $config, Event $event, ArrayCollection $logs)
    {
        if (!$logs->count()) {
            return;
        }

        foreach ($logs as $log) {
            $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_EXECUTED,
                new ExecutedEvent($config, $log)
            );
        }

        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_EXECUTED_BATCH,
            new ExecutedBatchEvent($config, $event, $logs)
        );
    }

    /**
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $logs
     */
    private function dispatchedFailedEvent(AbstractEventAccessor $config, ArrayCollection $logs)
    {
        if (!$logs->count()) {
            return;
        }

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $this->logger->debug(
                'CAMPAIGN: '.ucfirst($log->getEvent()->getEventType()).' ID# '.$log->getEvent()->getId().' for contact ID# '.$log->getLead()->getId()
            );

            $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_FAILED,
                new FailedEvent($config, $log)
            );

            $this->notificationHelper->notifyOfFailure($log->getLead(), $log->getEvent());
        }

        $this->scheduler->rescheduleFailures($logs);
    }

    /**
     * @param ArrayCollection $pending
     * @param ArrayCollection $success
     * @param ArrayCollection $failed
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     */
    private function validateProcessedLogs(ArrayCollection $pending, ArrayCollection $success, ArrayCollection $failed)
    {
        foreach ($pending as $log) {
            if (!$success->contains($log) && !$failed->contains($log)) {
                throw new LogNotProcessedException($log);
            }

            if ($success->contains($log) && $failed->contains($log)) {
                throw new LogPassedAndFailedException($log);
            }
        }
    }
}
