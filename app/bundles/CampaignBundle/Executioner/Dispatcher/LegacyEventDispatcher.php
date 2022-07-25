<?php

namespace Mautic\CampaignBundle\Executioner\Dispatcher;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignDecisionEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\Event\EventArrayTrait;
use Mautic\CampaignBundle\Event\ExecutedBatchEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LegacyEventDispatcher.
 *
 * @deprecated 2.13.0 to be removed in 3.0; BC support for old listeners
 */
class LegacyEventDispatcher
{
    use EventArrayTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    /**
     * LegacyEventDispatcher constructor.
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        EventScheduler $scheduler,
        LoggerInterface $logger,
        NotificationHelper $notificationHelper,
        MauticFactory $factory,
        ContactTracker $contactTracker
    ) {
        $this->dispatcher         = $dispatcher;
        $this->scheduler          = $scheduler;
        $this->logger             = $logger;
        $this->notificationHelper = $notificationHelper;
        $this->factory            = $factory;
        $this->contactTracker     = $contactTracker;
    }

    /**
     * @param $wasBatchProcessed
     */
    public function dispatchCustomEvent(
        AbstractEventAccessor $config,
        ArrayCollection $logs,
        $wasBatchProcessed,
        PendingEvent $pendingEvent
    ) {
        $settings = $config->getConfig();

        if (!isset($settings['eventName']) && !isset($settings['callback'])) {
            // Bad plugin but only fail if the new event didn't already process the log
            if (!$wasBatchProcessed) {
                $pendingEvent->failAll('Invalid event configuration');
            }

            return;
        }

        $rescheduleFailures = new ArrayCollection();

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $this->contactTracker->setSystemContact($log->getLead());

            if (isset($settings['eventName'])) {
                $result = $this->dispatchEventName($settings['eventName'], $settings, $log);
            } else {
                if (!is_callable($settings['callback'])) {
                    // No use to keep trying for the other logs as it won't ever work
                    break;
                }

                $result = $this->dispatchCallback($settings, $log);
            }

            // If the new batch event was handled, the $log was already processed so only process legacy logs if false
            if (!$wasBatchProcessed) {
                $this->dispatchExecutionEvent($config, $log, $result);

                if (!is_bool($result)) {
                    $log->appendToMetadata($result);
                }

                // Dispatch new events for legacy processed logs
                if ($this->isFailed($result)) {
                    $this->processFailedLog($result, $log, $pendingEvent);

                    $rescheduleFailures->set($log->getId(), $log);

                    $this->dispatchFailedEvent($config, $log);

                    continue;
                }

                if (is_array($result) && !empty($result['failed']) && isset($result['reason'])) {
                    $pendingEvent->passWithError($log, (string) $result['reason']);
                } else {
                    $pendingEvent->pass($log);
                }

                $this->dispatchExecutedEvent($config, $log);
            }
        }

        if ($rescheduleFailures->count()) {
            $this->scheduler->rescheduleFailures($rescheduleFailures);
        }

        $this->contactTracker->setSystemContact(null);
    }

    /**
     * Execute the new ON_EVENT_FAILED and ON_EVENT_EXECUTED events for logs processed by BC code.
     */
    public function dispatchExecutionEvents(AbstractEventAccessor $config, ArrayCollection $success, ArrayCollection $failures)
    {
        foreach ($success as $log) {
            $this->dispatchExecutionEvent($config, $log, true);
        }

        foreach ($failures as $log) {
            $this->dispatchExecutionEvent($config, $log, false);
        }
    }

    public function dispatchDecisionEvent(DecisionEvent $decisionEvent)
    {
        if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_DECISION_TRIGGER)) {
            $log   = $decisionEvent->getLog();
            $event = $log->getEvent();

            $legacyDecisionEvent = $this->dispatcher->dispatch(
                CampaignEvents::ON_EVENT_DECISION_TRIGGER,
                new CampaignDecisionEvent(
                    $log->getLead(),
                    $event->getType(),
                    $decisionEvent->getEventConfig()->getConfig(),
                    $this->getLegacyEventsArray($log),
                    $this->getLegacyEventsConfigArray($event, $decisionEvent->getEventConfig()),
                    0 === $event->getOrder(),
                    [$log]
                )
            );

            if ($legacyDecisionEvent->wasDecisionTriggered()) {
                $decisionEvent->setAsApplicable();
            }
        }
    }

    /**
     * @param $eventName
     *
     * @return bool
     */
    private function dispatchEventName($eventName, array $settings, LeadEventLog $log)
    {
        @trigger_error('eventName is deprecated. Convert to using batchEventName.', E_USER_DEPRECATED);

        $campaignEvent = new CampaignExecutionEvent(
            [
                'eventSettings'   => $settings,
                'eventDetails'    => null,
                'event'           => $log->getEvent(),
                'lead'            => $log->getLead(),
                'systemTriggered' => $log->getSystemTriggered(),
            ],
            null,
            $log
        );

        $this->dispatcher->dispatch($eventName, $campaignEvent);

        if ($channel = $campaignEvent->getChannel()) {
            $log->setChannel($channel)
                ->setChannelId($campaignEvent->getChannelId());
        }

        return $campaignEvent->getResult();
    }

    /**
     * @return mixed
     */
    private function dispatchCallback(array $settings, LeadEventLog $log)
    {
        @trigger_error('callback is deprecated. Convert to using batchEventName.', E_USER_DEPRECATED);

        $eventArray = $this->getEventArray($log->getEvent());
        $args       = [
            'eventSettings'   => $settings,
            'eventDetails'    => null, // @todo fix when procesing decisions,
            'event'           => $eventArray,
            'lead'            => $log->getLead(),
            'factory'         => $this->factory,
            'systemTriggered' => $log->getSystemTriggered(),
            'config'          => $eventArray['properties'],
        ];

        try {
            if (is_array($settings['callback'])) {
                $reflection = new \ReflectionMethod($settings['callback'][0], $settings['callback'][1]);
            } elseif (false !== strpos($settings['callback'], '::')) {
                $parts      = explode('::', $settings['callback']);
                $reflection = new \ReflectionMethod($parts[0], $parts[1]);
            } else {
                $reflection = new \ReflectionMethod(null, $settings['callback']);
            }

            $pass = [];
            foreach ($reflection->getParameters() as $param) {
                if (isset($args[$param->getName()])) {
                    $pass[] = $args[$param->getName()];
                } else {
                    $pass[] = null;
                }
            }

            return $reflection->invokeArgs($this, $pass);
        } catch (\ReflectionException $exception) {
            return false;
        }
    }

    /**
     * @param $result
     */
    private function dispatchExecutionEvent(AbstractEventAccessor $config, LeadEventLog $log, $result)
    {
        $eventArray = $this->getEventArray($log->getEvent());

        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_EXECUTION,
            new CampaignExecutionEvent(
                [
                    'eventSettings'   => $config->getConfig(),
                    'eventDetails'    => null, // @todo fix when procesing decisions,
                    'event'           => $eventArray,
                    'lead'            => $log->getLead(),
                    'systemTriggered' => $log->getSystemTriggered(),
                    'config'          => $eventArray['properties'],
                ],
                $result,
                $log
            )
        );
    }

    private function dispatchExecutedEvent(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_EXECUTED,
            new ExecutedEvent($config, $log)
        );

        $collection = new ArrayCollection();
        $collection->set($log->getId(), $log);
        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_EXECUTED_BATCH,
            new ExecutedBatchEvent($config, $log->getEvent(), $collection)
        );
    }

    private function dispatchFailedEvent(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_FAILED,
            new FailedEvent($config, $log)
        );

        $this->notificationHelper->notifyOfFailure($log->getLead(), $log->getEvent());
    }

    /**
     * @param $result
     *
     * @return bool
     */
    private function isFailed($result)
    {
        return
            false === $result
            || (is_array($result) && isset($result['result']) && false === $result['result']);
    }

    /**
     * @param $result
     */
    private function processFailedLog($result, LeadEventLog $log, PendingEvent $pendingEvent)
    {
        $this->logger->debug(
            'CAMPAIGN: '.ucfirst($log->getEvent()->getEventType()).' ID# '.$log->getEvent()->getId().' for contact ID# '.$log->getLead()->getId()
        );

        $metadata = $log->getMetadata();

        $reason = null;
        if (isset($metadata['errors'])) {
            $reason = (is_array($metadata['errors'])) ? implode('<br />', $metadata['errors']) : $metadata['errors'];
        } elseif (isset($metadata['reason'])) {
            $reason = $metadata['reason'];
        }

        $pendingEvent->fail($log, $reason);
    }
}
