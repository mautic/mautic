<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

interface ChannelTimelineInterface
{
    /**
     * Return the name of a template to use to customize the channel's timeline entry.
     *
     * Return an empty value to ignore
     *
     * @param string $eventType
     *
     * @return mixed
     */
    public function getChannelTimelineTemplate($eventType, array $details);

    /**
     * Override the timeline name for this channel's timeline entry.
     *
     * Return an empty value to ignore
     *
     * @param string $eventType
     *
     * @return mixed
     */
    public function getChannelTimelineLabel($eventType, array $details);

    /**
     * Override the icon for this channel's timeline entry.
     *
     * Return an empty value to ignore
     *
     * @param string $eventType
     *
     * @return mixed
     */
    public function getChannelTimelineIcon($eventType, array $details);
}
