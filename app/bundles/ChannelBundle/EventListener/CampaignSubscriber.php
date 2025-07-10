<?php

declare(strict_types=1);

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
use Mautic\ChannelBundle\Form\Type\MessageSendType;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\ChannelBundle\PreferenceBuilder\PreferenceBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    private ?Event $pseudoEvent = null;

    private ?ArrayCollection $mmLogs = null;

    /**
     * @var mixed[]
     */
    private array $messageChannels = [];

    public function __construct(
        private MessageModel $messageModel,
        private ActionDispatcher $actionDispatcher,
        private EventCollector $eventCollector,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            ChannelEvents::ON_CAMPAIGN_BATCH_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
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
            'batchEventName'         => ChannelEvents::ON_CAMPAIGN_BATCH_ACTION,
            'formType'               => MessageSendType::class,
            'channel'                => 'channel.message',
            'channelIdField'         => 'marketingMessage',
            'connectionRestrictions' => [
                'target' => [
                    'decision' => $decisions,
                ],
            ],
            'timelineTemplate'       => '@MauticChannel/SubscribedEvents/Timeline/index.html.twig',
            'timelineTemplateVars'   => [
                'messageSettings' => $channels,
            ],
        ];
        $event->addAction('message.send', $action);
    }

    /**
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \ReflectionException
     */
    public function onCampaignTriggerAction(PendingEvent $pendingEvent): void
    {
        $this->pseudoEvent = clone $pendingEvent->getEvent();
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

                $this->passExecutedLogs($pendingEvent, $successfullyExecuted, $preferenceBuilder);
            }
            ++$priority;
        }

        // Remove logs from failures if they are also in successful logs
        // This handles Marketing Messages with multiple channels where one channel fails but another succeeds.
        $this->removeSuccessfulFromFailures($pendingEvent);

        $pendingEvent->failRemainingPending($this->translator->trans('mautic.channel.message.failed'));
    }

    /**
     * @param string $channel
     *
     * @return bool|ArrayCollection
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \ReflectionException
     */
    private function sendChannelMessage(ArrayCollection $logs, $channel, array $messageChannel)
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

    private function passExecutedLogs(PendingEvent $pendingEvent, ArrayCollection $logs, PreferenceBuilder $channelPreferences): void
    {
        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            // Remove those successfully executed from being processed again for lower priorities
            $channelPreferences->removeLogFromAllChannels($log);

            // Find the Marketing Message log and pass it
            $mmLog = $pendingEvent->findLogByContactId($log->getLead()->getId());

            // Pass these for the MM campaign event
            $pendingEvent->pass($mmLog);
        }
    }

    /**
     * @param ArrayCollection<int,LeadEventLog> $success
     */
    private function removePsuedoFailures(ArrayCollection $success): void
    {
        foreach ($success as $key => $log) {
            if (!empty($log->getMetadata()['failed'])) {
                $success->remove($key);
            }
        }
    }

    private function removeSuccessfulFromFailures(PendingEvent $pendingEvent): void
    {
        $successfulKeys = $pendingEvent->getSuccessful()->getKeys();
        foreach ($successfulKeys as $key) {
            if ($pendingEvent->getFailures()->containsKey($key)) {
                $pendingEvent->getFailures()->remove($key);
            }
        }
    }

    private function recordChannelMetadata(PendingEvent $pendingEvent, string $channel): void
    {
        /** @var LeadEventLog $log */
        foreach ($this->mmLogs as $log) {
            try {
                $channelLog = $pendingEvent->findLogByContactId($log->getLead()->getId());

                if ($metadata = $channelLog->getMetadata()) {
                    $log->appendToMetadata([$channel => $metadata]);
                }
            } catch (NoContactsFoundException) {
                continue;
            }
        }
    }
}
