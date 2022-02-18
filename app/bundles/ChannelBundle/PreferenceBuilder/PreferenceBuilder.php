<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\PreferenceBuilder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\DoNotContact;
use Psr\Log\LoggerInterface;

class PreferenceBuilder
{
    /**
     * @var array<string, ChannelPreferences>
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
     * @param Collection<int, LeadEventLog> $logs
     * @param array<string, ChannelPreferences> $channels
     */
    public function __construct(Collection $logs, Event $event, array $channels, LoggerInterface $logger)
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

    /**
     * @param Collection<int, LeadEventLog> $logs
     * @param array<string, ChannelPreferences> $channels
     * @return void
     */
    private function buildRules(Collection $logs, array $channels)
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
