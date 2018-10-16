<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\ChannelBundle\PreferenceBuilder\PreferenceBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber implements EventSubscriberInterface
{
    /**
     * @var MessageModel
     */
    protected $messageModel;

    /**
     * @var ActionDispatcher
     */
    private $actionDispatcher;

    /**
     * @var EventCollector
     */
    private $eventCollector;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Event
     */
    private $pseudoEvent;

    /**
     * @var PendingEvent
     */
    private $pendingEvent;

    /**
     * @var ArrayCollection
     */
    private $mmLogs;

    /**
     * @var array
     */
    protected $messageChannels = [];

    /**
     * CampaignSubscriber constructor.
     *
     * @param MessageModel        $messageModel
     * @param ActionDispatcher    $actionDispatcher
     * @param EventCollector      $collector
     * @param LoggerInterface     $logger
     * @param TranslatorInterface $translator
     */
    public function __construct(
        MessageModel $messageModel,
        ActionDispatcher $actionDispatcher,
        EventCollector $collector,
        LoggerInterface $logger,
        TranslatorInterface $translator
    ) {
        $this->messageModel     = $messageModel;
        $this->actionDispatcher = $actionDispatcher;
        $this->eventCollector   = $collector;
        $this->logger           = $logger;
        $this->translator       = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            ChannelEvents::ON_CAMPAIGN_BATCH_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $channels  = $this->messageModel->getChannels();
        $decisions = [];
        foreach ($channels as $channel) {
            if (isset($channel['campaignDecisionsSupported'])) {
                $decisions = $decisions + $channel['campaignDecisionsSupported'];
            }
        }

        $action = [
            'label'                  => 'mautic.channel.message.send.marketing.message',
            'description'            => 'mautic.channel.message.send.marketing.message.descr',
            'eventName'              => ChannelEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'batchEventName'         => ChannelEvents::ON_CAMPAIGN_BATCH_ACTION,
            'formType'               => 'message_send',
            'formTheme'              => 'MauticChannelBundle:FormTheme\MessageSend',
            'channel'                => 'channel.message',
            'channelIdField'         => 'marketingMessage',
            'connectionRestrictions' => [
                'target' => [
                    'decision' => $decisions,
                ],
            ],
            'timelineTemplate'       => 'MauticChannelBundle:SubscribedEvents\Timeline:index.html.php',
            'timelineTemplateVars'   => [
                'messageSettings' => $channels,
            ],
        ];
        $event->addAction('message.send', $action);
    }

    /**
     * @param PendingEvent $pendingEvent
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \ReflectionException
     */
    public function onCampaignTriggerAction(PendingEvent $pendingEvent)
    {
        $this->pendingEvent = $pendingEvent;
        $this->pseudoEvent  = clone $pendingEvent->getEvent();
        $this->pseudoEvent->setCampaign($pendingEvent->getEvent()->getCampaign());

        $this->mmLogs    = $pendingEvent->getPending();
        $campaignEvent   = $pendingEvent->getEvent();
        $properties      = $campaignEvent->getProperties();
        $messageSettings = $this->messageModel->getChannels();
        $id              = (int) $properties['marketingMessage'];

        // Set channel for the event logs
        $pendingEvent->setChannel('channel.message', $id);

        if (!isset($this->messageChannels[$id])) {
            $this->messageChannels[$id] = $this->messageModel->getMessageChannels($id);
        }

        // organize into preferred channels
        $preferenceBuilder = new PreferenceBuilder($this->mmLogs, $this->pseudoEvent, $this->messageChannels[$id], $this->logger);

        // Loop until we have no more channels
        $priority           = 1;
        $channelPreferences = $preferenceBuilder->getChannelPreferences();

        while ($priority <= count($this->messageChannels[$id])) {
            foreach ($channelPreferences as $channel => $preferences) {
                if (!isset($messageSettings[$channel]['campaignAction'])) {
                    continue;
                }

                $channelLogs = $preferences->getLogsByPriority($priority);
                if (!$channelLogs->count()) {
                    continue;
                }

                // Marketing messages mimick campaign actions so create a pseudo event
                $this->pseudoEvent->setEventType(Event::TYPE_ACTION)
                    ->setType($messageSettings[$channel]['campaignAction']);

                $successfullyExecuted = $this->sendChannelMessage($channelLogs, $channel, $this->messageChannels[$id][$channel]);

                $this->passExecutedLogs($successfullyExecuted, $preferenceBuilder);
            }
            ++$priority;
        }

        $pendingEvent->failRemaining($this->translator->trans('mautic.channel.message.failed'));
    }

    /**
     * @param ArrayCollection $logs
     * @param string          $channel
     * @param array           $messageChannel
     *
     * @return bool|ArrayCollection
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \ReflectionException
     */
    protected function sendChannelMessage(ArrayCollection $logs, $channel, array $messageChannel)
    {
        /** @var ActionAccessor $config */
        $config = $this->eventCollector->getEventConfig($this->pseudoEvent);

        // Set the property set as the channel ID with the message ID
        if ($channelIdField = $config->getChannelIdField()) {
            $messageChannel['properties'][$channelIdField] = $messageChannel['channel_id'];
        }

        $this->pseudoEvent->setProperties($messageChannel['properties']);

        // Dispatch the mimicked campaign action
        $pendingEvent = new PendingEvent($config, $this->pseudoEvent, $logs);
        $pendingEvent->setChannel('campaign.event', $messageChannel['channel_id']);

        $this->actionDispatcher->dispatchEvent(
            $config,
            $this->pseudoEvent,
            $logs,
            $pendingEvent
        );

        // Record the channel metadata mainly for debugging
        $this->recordChannelMetadata($pendingEvent, $channel);

        // Remove pseudo failures so we can try the next channel
        $success = $pendingEvent->getSuccessful();
        $this->removePsuedoFailures($success);

        unset($pendingEvent);

        return $success;
    }

    /**
     * @param ArrayCollection   $logs
     * @param PreferenceBuilder $channelPreferences
     */
    private function passExecutedLogs(ArrayCollection $logs, PreferenceBuilder $channelPreferences)
    {
        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            // Remove those successfully executed from being processed again for lower priorities
            $channelPreferences->removeLogFromAllChannels($log);

            // Find the Marketing Message log and pass it
            $mmLog = $this->pendingEvent->findLogByContactId($log->getLead()->getId());

            // Pass these for the MM campaign event
            $this->pendingEvent->pass($mmLog);
        }
    }

    /**
     * @param ArrayCollection $success
     */
    private function removePsuedoFailures(ArrayCollection $success)
    {
        /**
         * @var int
         * @var LeadEventLog $log
         */
        foreach ($success as $key => $log) {
            if (!empty($log->getMetadata()['failed'])) {
                $success->remove($key);
            }
        }
    }

    /**
     * @param PendingEvent    $pendingEvent
     * @param ArrayCollection $mmLogs
     * @param                 $channel
     */
    private function recordChannelMetadata(PendingEvent $pendingEvent, $channel)
    {
        /** @var LeadEventLog $log */
        foreach ($this->mmLogs as $log) {
            try {
                $channelLog = $pendingEvent->findLogByContactId($log->getLead()->getId());

                if ($metadata = $channelLog->getMetadata()) {
                    $log->appendToMetadata([$channel => $metadata]);
                }
            } catch (NoContactsFoundException $exception) {
                continue;
            }
        }
    }
}
