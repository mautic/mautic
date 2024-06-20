<?php

namespace Mautic\CampaignBundle\Entity;

interface ChannelInterface
{
    /**
     * @return string
     */
    public function getChannel();

    public function setChannel($channel): void;

    /**
     * @return int
     */
    public function getChannelId();

    public function setChannelId($id): void;
}
