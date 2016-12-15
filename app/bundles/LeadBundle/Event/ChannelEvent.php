<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class ChannelEvent.
 */
class ChannelEvent extends CommonEvent
{
    protected $channels = [];
    /**
     * Returns the channel.
     *
     * @return string
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * Sets the channel.
     *
     * @param $channel
     */
    public function setChannel($channel)
    {
        $this->channels[$channel] = $channel;
    }
}
