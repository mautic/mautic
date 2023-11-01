<?php

namespace Mautic\CampaignBundle\Entity;

interface ChannelInterface
{
    /**
     * @return string
     */
    public function getChannel();

    /**
     * @param $channel
     *
     * @return ChannelInterface
     */
    public function setChannel($channel);

    /**
     * @return int
     */
    public function getChannelId();

    /**
     * @param $id
     *
     * @return ChannelInterface
     */
    public function setChannelId($id);
}
