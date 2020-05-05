<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
