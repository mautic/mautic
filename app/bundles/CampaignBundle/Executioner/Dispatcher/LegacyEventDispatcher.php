<?php

namespace Mautic\CampaignBundle\Executioner\Dispatcher;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\EventArrayTrait;
use Mautic\CampaignBundle\Event\ExecutedBatchEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated 2.13.0 to be removed in 3.0; BC support for old listeners
 */
class LegacyEventDispatcher
{
    use EventArrayTrait;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly EventScheduler $scheduler,
        private readonly LoggerInterface $logger,
        private readonly ContactTracker $contactTracker,
    ) {
    }

    public function dispatchCustomEvent(
        AbstractEventAccessor $config,
        ArrayCollection $logs,
        $wasBatchProcessed,
        PendingEvent $pendingEvent,
    ): void {
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
                $event  = $this->dispatchEventName($settings['eventName'], $settings, $log);
                $result = $event->getResult();
            } else {
                if (!is_callable($settings['callback'])) {
                    // No use to keep trying for the other logs as it won't ever work
                    break;
                }

                $result = $this->dispatchCallback($settings, $log);
            }

            // If the new batch event was handled, the $log was already processed so only process legacy logs if false
            if (!$wasBatchProcessed) {
                if (!is_bool($result)) {
                    $log->appendToMetadata($result);
                }

                // Dispatch new events for legacy processed logs
                if ($this->isFailed($result)) {
                    $this->processFailedLog($log, $pendingEvent);

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

        $this->contactTracker->setSystemContact();
    }

    private function dispatchEventName(?string $eventName, array $settings, LeadEventLog $log): CampaignExecutionEvent
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

        $this->dispatcher->dispatch($campaignEvent, $eventName);

        if ($channel = $campaignEvent->getChannel()) {
            $log->setChannel($channel);
            $log->setChannelId($campaignEvent->getChannelId());
        }

        return $campaignEvent;
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
            'systemTriggered' => $log->getSystemTriggered(),
            'config'          => $eventArray['properties'],
        ];

        try {
            if (is_array($settings['callback'])) {
                $reflection = new \ReflectionMethod($settings['callback'][0], $settings['callback'][1]);
            } elseif (str_contains($settings['callback'], '::')) {
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
        } catch (\ReflectionException) {
            return false;
        }
    }

    private function dispatchExecutedEvent(AbstractEventAccessor $config, LeadEventLog $log): void
    {
        $this->dispatcher->dispatch(
            new ExecutedEvent($config, $log),
            CampaignEvents::ON_EVENT_EXECUTED
        );

        $collection = new ArrayCollection();
        $collection->set($log->getId(), $log);
        $this->dispatcher->dispatch(
            new ExecutedBatchEvent($config, $log->getEvent(), $collection),
            CampaignEvents::ON_EVENT_EXECUTED_BATCH
        );
    }

    private function dispatchFailedEvent(AbstractEventAccessor $config, LeadEventLog $log): void
    {
        $this->dispatcher->dispatch(
            new FailedEvent($config, $log),
            CampaignEvents::ON_EVENT_FAILED
        );
    }

    private function isFailed(mixed $result): bool
    {
        return
            false === $result
            || (is_array($result) && isset($result['result']) && false === $result['result']);
    }

    private function processFailedLog(LeadEventLog $log, PendingEvent $pendingEvent): void
    {
        $this->logger->debug(
            'CAMPAIGN: '.ucfirst($log->getEvent()->getEventType() ?? 'unknown event').' ID# '.$log->getEvent()->getId().' for contact ID# '.$log->getLead()->getId()
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
