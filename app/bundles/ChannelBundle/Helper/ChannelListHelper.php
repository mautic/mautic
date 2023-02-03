<?php

namespace Mautic\ChannelBundle\Helper;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\Helper\Helper;

class ChannelListHelper extends Helper
{
    /**
     * @var Translator
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
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher, Translator $translator)
    {
        $this->translator = $translator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get contact channels.
     *
     * @return array
     */
    public function getChannelList()
    {
        $channels = [];
        foreach ($this->getChannels() as $channel => $details) {
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
        $this->setupChannels();

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

        if (1 === count($features)) {
            $channels = $channels[$features[0]];
        }

        return $channels;
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        $this->setupChannels();

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

    /**
     * Setup channels.
     *
     * Done this way to avoid a circular dependency error with LeadModel
     */
    protected function setupChannels()
    {
        if (!empty($this->channels)) {
            return;
        }

        $event                 = $this->dispatcher->dispatch(new ChannelEvent(), ChannelEvents::ADD_CHANNEL);
        $this->channels        = $event->getChannelConfigs();
        $this->featureChannels = $event->getFeatureChannels();
        unset($event);
    }
}
