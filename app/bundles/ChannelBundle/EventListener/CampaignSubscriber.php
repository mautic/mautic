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

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var MessageModel
     */
    protected $messageModel;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var EventModel
     */
    protected $eventModel;

    /**
     * @var array
     */
    protected $messageChannels = [];

    /**
     * CampaignSubscriber constructor.
     *
     * @param MessageModel  $messageModel
     * @param CampaignModel $campaignModel
     * @param EventModel    $eventModel
     */
    public function __construct(MessageModel $messageModel, CampaignModel $campaignModel, EventModel $eventModel)
    {
        $this->messageModel  = $messageModel;
        $this->campaignModel = $campaignModel;
        $this->eventModel    = $eventModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD         => ['onCampaignBuild', 0],
            ChannelEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
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
            'formType'               => 'message_send',
            'formTheme'              => 'MauticChannelBundle:FormTheme\MessageSend',
            'channel'                => 'channel.message',
            'channelIdField'         => 'marketingMessage',
            'connectionRestrictions' => [
                'target' => [
                    'decision' => $decisions,
                ],
            ],
            'timelineTemplate'     => 'MauticChannelBundle:SubscribedEvents\Timeline:index.html.php',
            'timelineTemplateVars' => [
                'messageSettings' => $channels,
            ],
        ];
        $event->addAction('message.send', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $messageSettings = $this->messageModel->getChannels();
        $id              = (int) $event->getConfig()['marketingMessage'];
        if (!isset($this->messageChannels[$id])) {
            $this->messageChannels[$id] = $this->messageModel->getMessageChannels($id);
        }
        $lead           = $event->getLead();
        $channelRules   = $lead->getChannelRules();
        $result         = false;
        $channelResults = [];

        // Use preferred channels first
        $tryChannels = $this->messageChannels[$id];
        foreach ($channelRules as $channel => $rule) {
            if ($rule['dnc'] !== DoNotContact::IS_CONTACTABLE) {
                unset($tryChannels[$channel]);
                $channelResults[$channel] = [
                    'failed' => 1,
                    'dnc'    => $rule['dnc'],
                ];

                continue;
            }

            if (isset($tryChannels[$channel])) {
                $messageChannel = $tryChannels[$channel];

                // Remove this channel so that any non-preferred channels can be used as a last resort
                unset($tryChannels[$channel]);

                // Attempt to send the message
                if (isset($messageSettings[$channel])) {
                    $result = $this->sendChannelMessage($channel, $messageChannel, $messageSettings[$channel], $event, $channelResults);
                }
            }
        }

        if (!$result && count($tryChannels)) {
            // All preferred channels were a no go so try whatever is left
            foreach ($tryChannels as $channel => $messageChannel) {
                // Attempt to send the message through other channels
                if (isset($messageSettings[$channel])) {
                    if ($this->sendChannelMessage($channel, $messageChannel, $messageSettings[$channel], $event, $channelResults)) {
                        break;
                    }
                }
            }
        }

        return $event->setResult($channelResults);
    }

    /**
     * @param                        $channel
     * @param                        $messageChannel
     * @param                        $settings
     * @param CampaignExecutionEvent $event
     * @param                        $channelResults
     *
     * @return bool|mixed
     */
    protected function sendChannelMessage($channel, $messageChannel, $settings, CampaignExecutionEvent $event, &$channelResults)
    {
        if (!isset($settings['campaignAction'])) {
            return false;
        }

        $eventSettings  = $this->campaignModel->getEvents();
        $campaignAction = $settings['campaignAction'];

        $result = false;
        if (isset($eventSettings['action'][$campaignAction])) {
            $campaignEventSettings      = $eventSettings['action'][$campaignAction];
            $messageEvent               = $event->getEvent();
            $messageEvent['type']       = $campaignAction;
            $messageEvent['properties'] = $messageChannel['properties'];

            // Set the property set as the channel ID with the message ID
            if (isset($campaignEventSettings['channelIdField'])) {
                $messageEvent['properties'][$campaignEventSettings['channelIdField']] = $messageChannel['channel_id'];
            }

            $result = $this->eventModel->invokeEventCallback(
                $messageEvent,
                $campaignEventSettings,
                $event->getLead(),
                null,
                $event->getSystemTriggered()
            );

            $channelResults[$channel] = $result;
            if ($result) {
                if (is_array($result) && !empty($result['failed'])) {
                    $result = false;
                } elseif (!$event->getChannel()) {
                    $event->setChannel($channel, $messageChannel['channel_id']);
                }
            }
        }

        return $result;
    }
}
