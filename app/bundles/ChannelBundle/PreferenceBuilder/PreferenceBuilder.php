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
     *
     * @param ArrayCollection $logs
     * @param Event           $event
     * @param array           $channels
     * @param LoggerInterface $logger
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

    /**
     * @param LeadEventLog $log
     */
    public function removeLogFromAllChannels(LeadEventLog $log)
    {
        foreach ($this->channels as $channelPreferences) {
            $channelPreferences->removeLog($log);
        }
    }

    /**
     * @param string       $channel
     * @param array        $rule
     * @param LeadEventLog $log
     * @param int          $priority
     */
    private function addChannelRule($channel, array $rule, LeadEventLog $log, $priority)
    {
        $channelPreferences = $this->getChannelPreferenceObject($channel, $priority);

        if ($rule['dnc'] !== DoNotContact::IS_CONTACTABLE) {
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
            $this->channels[$channel] = new ChannelPreferences($channel, $this->event, $this->logger);
        }

        $this->channels[$channel]->addPriority($priority);

        return $this->channels[$channel];
    }

    /**
     * @param ArrayCollection $logs
     * @param array           $channels
     */
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
