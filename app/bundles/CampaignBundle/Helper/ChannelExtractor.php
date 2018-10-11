<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Helper;

use Mautic\CampaignBundle\Entity\ChannelInterface;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ChannelExtractor
{
    /**
     * @param ChannelInterface      $entity
     * @param Event                 $event
     * @param AbstractEventAccessor $eventConfig
     */
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
     * @param array  $properties
     * @param string $channelIdField
     *
     * @return null|int
     */
    private static function getChannelId(array $properties, $channelIdField)
    {
        if (empty($properties[$channelIdField])) {
            return null;
        }

        $channelId = $properties[$channelIdField];
        if (is_array($channelId) && (count($channelId) === 1)) {
            // Only store channel ID if a single item was selected
            $channelId = reset($channelId);
        }

        if (!is_numeric($channelId)) {
            return null;
        }

        return (int) $channelId;
    }
}
