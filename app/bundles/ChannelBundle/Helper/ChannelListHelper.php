<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Helper;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ChannelListHelper.
 */
class ChannelListHelper extends Helper
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $channels = [];

    /**
     * @var array
     */
    protected $featureChannels = [];

    /**
     * ChannelListHelper constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param TranslatorInterface      $translator
     */
    public function __construct(EventDispatcherInterface $dispatcher, TranslatorInterface $translator)
    {
        $this->translator      = $translator;
        $event                 = $dispatcher->dispatch(ChannelEvents::ADD_CHANNEL, new ChannelEvent());
        $this->channels        = $event->getChannelConfigs();
        $this->featureChannels = $event->getFeatureChannels();
        unset($event);

        // @deprecated 2.4 to be removed 3.0; BC support
        if ($dispatcher->hasListeners(\Mautic\LeadBundle\LeadEvents::ADD_CHANNEL)) {
            $event                 = $dispatcher->dispatch(\Mautic\LeadBundle\LeadEvents::ADD_CHANNEL, new \Mautic\LeadBundle\Event\ChannelEvent());
            $this->channels        = array_merge($this->channels, $event->getChannelConfigs());
            $this->featureChannels = array_merge($this->featureChannels, $event->getFeatureChannels());
            unset($event);
        }
    }

    /**
     * Get contact channels.
     *
     * @return array
     */
    public function getChannelList()
    {
        $channels = [];
        foreach ($this->channels as $channel => $details) {
            $channelName            = isset($details['label']) ? $this->translator->trans($details['label']) : $this->getChannelLabel($channel);
            $channels[$channelName] = $channel;
        }

        return $channels;
    }

    /**
     * @param      $features
     * @param bool $listOnly
     *
     * @return array
     */
    public function getFeatureChannels($features, $listOnly = false)
    {
        if (!is_array($features)) {
            $features = [$features];
        }

        $channels = [];
        foreach ($features as $feature) {
            $featureChannels = (isset($this->featureChannels[$feature])) ? $this->featureChannels[$feature] : [];
            $returnChannels  = [];
            foreach ($featureChannels as $channel => $details) {
                if (!isset($details['label'])) {
                    $featureChannels[$channel]['label'] = $this->getChannelLabel($channel);
                }

                if ($listOnly) {
                    $returnChannels[$featureChannels[$channel]['label']] = $channel;
                } else {
                    $returnChannels[$channel] = $featureChannels[$channel];
                }
            }
            unset($featureChannels);
            $channels[$feature] = $returnChannels;
        }

        if (count($features) === 1) {
            $channels = $channels[$features[0]];
        }

        return $channels;
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * @param $channel
     *
     * @return string
     */
    public function getChannelLabel($channel)
    {
        switch (true) {
            case $this->translator->hasId('mautic.channel.'.$channel):
                return $this->translator->trans('mautic.channel.'.$channel);
            case $this->translator->hasId('mautic.'.$channel.'.'.$channel):
                return $this->translator->trans('mautic.'.$channel.'.'.$channel);
            default:
                return ucfirst($channel);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'chanel';
    }
}
