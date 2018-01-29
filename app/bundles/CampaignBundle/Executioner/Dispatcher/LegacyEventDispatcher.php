<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
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
use Mautic\CampaignBundle\Entity\FailedLeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\EventArrayTrait;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Factory\MauticFactory;
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
     * @var MauticFactory
     */
    private $factory;

    /**
     * LegacyEventDispatcher constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param EventScheduler           $scheduler
     * @param LoggerInterface          $logger
     * @param MauticFactory            $factory
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        EventScheduler $scheduler,
        LoggerInterface $logger,
        MauticFactory $factory
    ) {
        $this->dispatcher    = $dispatcher;
        $this->scheduler     = $scheduler;
        $this->logger        = $logger;
        $this->factory       = $factory;
    }

    /**
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $logs
     * @param bool                  $wasBatchProcessed
     */
    public function dispatchCustomEvent(AbstractEventAccessor $config, Event $event, ArrayCollection $logs, $wasBatchProcessed)
    {
        $settings = $config->getConfig();

        if (!isset($settings['eventName']) && !isset($settings['callback'])) {
            return;
        }

        $eventArray = $this->getEventArray($event);

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            if (isset($settings['eventName'])) {
                $result = $this->dispatchEventName($settings['eventName'], $settings, $eventArray, $log);
            } else {
                if (!is_callable($settings['callback'])) {
                    // No use to keep trying for the other logs as it won't ever work
                    break;
                }

                $result = $this->dispatchCallback($settings, $eventArray, $log);
            }

            if (!$wasBatchProcessed) {
                $this->dispatchExecutionEvent($config, $log, $result);

                // Dispatch new events for legacy processed logs
                if ($this->isFailed($result)) {
                    $this->processFailedLog($result, $log);
                    $this->scheduler->rescheduleFailure($log);

                    $this->dispatchFailedEvent($config, $log);

                    return;
                }

                $this->dispatchExecutedEvent($config, $log);
            }
        }
    }

    /**
     * Execute the new ON_EVENT_FAILED and ON_EVENT_EXECUTED events for logs processed by BC code.
     *
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $success
     * @param ArrayCollection       $failures
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

    /**
     * @param              $eventName
     * @param array        $settings
     * @param array        $eventArray
     * @param LeadEventLog $log
     *
     * @return bool
     */
    private function dispatchEventName($eventName, array $settings, array $eventArray, LeadEventLog $log)
    {
        @trigger_error('eventName is deprecated. Convert to using batchEventName.', E_USER_DEPRECATED);

        // Create a campaign event with a default successful result
        $campaignEvent = new CampaignExecutionEvent(
            [
                'eventSettings'   => $settings,
                'eventDetails'    => null, // @todo fix when procesing decisions,
                'event'           => $eventArray,
                'lead'            => $log->getLead(),
                'systemTriggered' => $log->getSystemTriggered(),
                'config'          => $eventArray['properties'],
            ],
            null,
            $log
        );

        $this->dispatcher->dispatch($eventName, $campaignEvent);

        $result = $campaignEvent->getResult();

        $log->setChannel($campaignEvent->getChannel())
            ->setChannelId($campaignEvent->getChannelId());

        return $result;
    }

    /**
     * @param array        $settings
     * @param array        $eventArray
     * @param LeadEventLog $log
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    private function dispatchCallback(array $settings, array $eventArray, LeadEventLog $log)
    {
        @trigger_error('callback is deprecated. Convert to using batchEventName.', E_USER_DEPRECATED);

        $args = [
            'eventSettings'   => $settings,
            'eventDetails'    => null, // @todo fix when procesing decisions,
            'event'           => $eventArray,
            'lead'            => $log->getLead(),
            'factory'         => $this->factory,
            'systemTriggered' => $log->getSystemTriggered(),
            'config'          => $eventArray['properties'],
        ];

        if (is_array($settings['callback'])) {
            $reflection = new \ReflectionMethod($settings['callback'][0], $settings['callback'][1]);
        } elseif (strpos($settings['callback'], '::') !== false) {
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
    }

    /**
     * @param AbstractEventAccessor $config
     * @param LeadEventLog          $log
     * @param                       $result
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

    /**
     * @param AbstractEventAccessor $config
     * @param LeadEventLog          $log
     */
    private function dispatchExecutedEvent(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_EXECUTED,
            new ExecutedEvent($config, $log)
        );
    }

    /**
     * @param AbstractEventAccessor $config
     * @param LeadEventLog          $log
     */
    private function dispatchFailedEvent(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_FAILED,
            new FailedEvent($config, $log)
        );
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
            || (is_array($result) && isset($result['result']) && false === $result['result'])
        ;
    }

    /**
     * @param              $result
     * @param LeadEventLog $log
     */
    private function processFailedLog($result, LeadEventLog $log)
    {
        $this->logger->debug(
            'CAMPAIGN: '.ucfirst($log->getEvent()->getEventType()).' ID# '.$log->getEvent()->getId().' for contact ID# '.$log->getLead()->getId()
        );

        if (is_array($result)) {
            $log->setMetadata($result);
        }

        $metadata = $log->getMetadata();
        if (is_array($result)) {
            $metadata = array_merge($metadata, $result);
        }

        $reason = null;
        if (isset($metadata['errors'])) {
            $reason = (is_array($metadata['errors'])) ? implode('<br />', $metadata['errors']) : $metadata['errors'];
        } elseif (isset($metadata['reason'])) {
            $reason = $metadata['reason'];
        }

        if (!$failedLog = $log->getFailedLog()) {
            $failedLog = new FailedLeadEventLog();
        }

        $failedLog->setLog($log)
            ->setDateAdded(new \DateTime())
            ->setReason($reason);

        $log->setFailedLog($failedLog);
    }
}
