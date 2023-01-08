<?php

namespace Mautic\CampaignBundle\Helper;

use Mautic\CampaignBundle\Entity\ChannelInterface;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ChannelExtractor
{
    public static function setChannel(ChannelInterface $entity, Event $event, AbstractEventAccessor $eventConfig)
    {
        // Allow event to update itself
        $isSelf = $entity === $event;

        if (!$isSelf && $entity->getChannel()) {
            return;
        }

        if (!$channel = $eventConfig->getChannel()) {
            return;
        }

        $entity->setChannel($channel);

        if (!$channelIdField = $eventConfig->getChannelIdField()) {
            return;
        }

        if (!$event->getProperties()) {
            return;
        }

        $entity->setChannelId(
            self::getChannelId($event->getProperties(), $channelIdField)
        );
    }

    /**
     * @param string $channelIdField
     *
     * @return int|null
     */
    private static function getChannelId(array $properties, $channelIdField)
    {
        if (empty($properties[$channelIdField])) {
            return null;
        }

        $channelId = $properties[$channelIdField];
        if (is_array($channelId) && (1 === count($channelId))) {
            // Only store channel ID if a single item was selected
            $channelId = reset($channelId);
        }

        if (!is_numeric($channelId)) {
            return null;
        }

        return (int) $channelId;
    }
}
