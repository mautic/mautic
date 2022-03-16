<?php

namespace Mautic\ChannelBundle\PreferenceBuilder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\DoNotContact;
use Psr\Log\LoggerInterface;

class PreferenceBuilder
{
    /**
     * @var ChannelPreferences[]
     */
    private $channels = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Event
     */
    private $event;

    /**
     * PreferenceBuilder constructor.
     */
    public function __construct(ArrayCollection $logs, Event $event, array $channels, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->event  = $event;

        $this->buildRules($logs, $channels);
    }

    /**
     * @return ChannelPreferences[]
     */
    public function getChannelPreferences()
    {
        return $this->channels;
    }

    public function removeLogFromAllChannels(LeadEventLog $log)
    {
        foreach ($this->channels as $channelPreferences) {
            $channelPreferences->removeLog($log);
        }
    }

    /**
     * @param string $channel
     * @param int    $priority
     */
    private function addChannelRule($channel, array $rule, LeadEventLog $log, $priority)
    {
        $channelPreferences = $this->getChannelPreferenceObject($channel, $priority);

        if (DoNotContact::IS_CONTACTABLE !== $rule['dnc']) {
            $log->appendToMetadata(
                [
                    $channel => [
                        'failed' => 1,
                        'dnc'    => $rule['dnc'],
                    ],
                ]
            );

            return;
        }

        $this->logger->debug("MARKETING MESSAGE: Set $channel as priority $priority for contact ID #".$log->getLead()->getId());

        $channelPreferences->addLog($log, $priority);
    }

    /**
     * @param string $channel
     *
     * @return ChannelPreferences
     */
    private function getChannelPreferenceObject($channel, $priority)
    {
        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = new ChannelPreferences($this->event);
        }

        $this->channels[$channel]->addPriority($priority);

        return $this->channels[$channel];
    }

    private function buildRules(ArrayCollection $logs, array $channels)
    {
        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $channelRules = $log->getLead()->getChannelRules();
            $allChannels  = $channels;
            $priority     = 1;

            // Build priority based on channel rules
            foreach ($channelRules as $channel => $rule) {
                $this->addChannelRule($channel, $rule, $log, $priority);
                ++$priority;
                unset($allChannels[$channel]);
            }

            // Add the rest of the channels as least priority
            foreach ($allChannels as $channel => $messageSettings) {
                $this->addChannelRule($channel, ['dnc' => DoNotContact::IS_CONTACTABLE], $log, $priority);
                ++$priority;
            }
        }
    }
}
