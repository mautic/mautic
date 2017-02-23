<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Event;

use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class ChannelEvent.
 */
class ChannelEvent extends CommonEvent
{
    /**
     * @var array
     */
    protected $channels = [];

    /**
     * @var array
     */
    protected $featureChannels = [];

    /**
     * Adds a submit action to the list of available actions.
     *
     * @param string $channel a unique identifier; it is recommended that it be namespaced if there are multiple entities in a channel  i.e. something.something
     * @param array  $config  Should be keyed by the feature it supports that contains an array of feature configuration options. i.e.
     *                        $config = [
     *                        MessageModel::CHANNEL_FEATURE => [
     *                        'lookupFormType'       => (optional) Form type class/alias for the channel lookup list,
     *                        'propertiesFormType'   => (optional) Form type class/alias for the channel properties if a lookup list is not used,
     *
     *                        'channelTemplate'      => (optional) template to inject UI/DOM into the bottom of the channel's tab
     *                        'formTheme'           => (optional) theme directory for custom form types
     *
     *                          ]
     *                       ]
     *
     * @return $this
     */
    public function addChannel($channel, array $config = [])
    {
        $this->channels[$channel] = $config;

        foreach ($config as $feature => $featureConfig) {
            $this->featureChannels[$feature][$channel] = $featureConfig;
        }

        return $this;
    }

    /**
     * Returns registered channels with their configs.
     *
     * @return array
     */
    public function getChannelConfigs()
    {
        return $this->channels;
    }

    /**
     * Returns repository name for the provided channel. Null if not found.
     *
     * @return string|null
     */
    public function getRepositoryName($channel)
    {
        if (isset($this->channels[$channel][MessageModel::CHANNEL_FEATURE]['repository'])) {
            return $this->channels[$channel][MessageModel::CHANNEL_FEATURE]['repository'];
        }

        return null;
    }

    /**
     * Returns the name of the column holding the channel name for the provided channel. Defaults to 'name'.
     *
     * @return string|null
     */
    public function getNameColumn($channel)
    {
        if (isset($this->channels[$channel][MessageModel::CHANNEL_FEATURE]['nameColumn'])) {
            return $this->channels[$channel][MessageModel::CHANNEL_FEATURE]['nameColumn'];
        }

        return 'name';
    }

    /**
     * @param $feature
     *
     * @return array
     */
    public function getFeatureChannels()
    {
        return $this->featureChannels;
    }

    /**
     * Set a preference center channel.
     *
     * @deprecated 2.4 to be removed 3.0; use addChannel()
     *
     * @param $channel
     */
    public function setChannel($channel)
    {
        $this->addChannel($channel, [LeadModel::CHANNEL_FEATURE => []]);
    }

    /**
     * Returns a list of channels.
     *
     * @deprecated 2.4 to be removed 3.0; use getChannelConfigs()
     *
     * @return string
     */
    public function getChannels()
    {
        return array_keys($this->channels);
    }
}
