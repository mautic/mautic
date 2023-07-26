<?php

namespace Mautic\CampaignBundle\Entity;

interface ChannelInterface
{
    /**
     * @return string
     */
    public function getChannel();

    /**
     * @return ChannelInterface
     */
    public function setChannel($channel);

    /**
     * @return int
     */
    public function getChannelId();

    /**
     * @return ChannelInterface
     */
    public function setChannelId($id);
}
