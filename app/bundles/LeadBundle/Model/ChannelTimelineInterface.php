<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

/**
 * Interface ChannelTimelineInterface.
 */
interface ChannelTimelineInterface
{
    /**
     * Return the name of a template to use to customize the channel's timeline entry.
     *
     * Return an empty value to ignore
     *
     * @param string $eventType
     * @param array  $details
     *
     * @return mixed
     */
    public function getChannelTimelineTemplate($eventType, $details);

    /**
     * Override the timeline name for this channel's timeline entry.
     *
     * Return an empty value to ignore
     *
     * @param string $eventType
     * @param array  $details
     *
     * @return mixed
     */
    public function getChannelTimelineLabel($eventType, $details);

    /**
     * Override the icon for this channel's timeline entry.
     *
     * Return an empty value to ignore
     *
     * @param string $eventType
     * @param array  $details
     *
     * @return mixed
     */
    public function getChannelTimelineIcon($eventType, $details);
}
