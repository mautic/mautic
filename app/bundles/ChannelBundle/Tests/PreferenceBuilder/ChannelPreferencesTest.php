<?php

namespace Mautic\ChannelBundle\Tests\PreferenceBuilder;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\ChannelBundle\PreferenceBuilder\ChannelPreferences;

class ChannelPreferencesTest extends \PHPUnit\Framework\TestCase
{
    public function testLogsAreOrganizedByPriority()
    {
        $campaign = new Campaign();
        $event    = new Event();
        $event->setCampaign($campaign);

        $channelPreferences = $this->getChannelPreference('email', $event);

        $log1 = new LeadEventLog();
        $log1->setEvent($event);
        $log1->setCampaign($campaign);
        $log1->setMetadata(['log' => 1]);
        $channelPreferences->addLog($log1, 1);

        $log2 = new LeadEventLog();
        $log2->setEvent($event);
        $log2->setCampaign($campaign);
        $log2->setMetadata(['log' => 2]);
        $channelPreferences->addLog($log2, 2);

        $organized = $channelPreferences->getLogsByPriority(1);
        $this->assertEquals($organized->first()->getMetadata()['log'], 1);

        $organized = $channelPreferences->getLogsByPriority(2);
        $this->assertEquals($organized->first()->getMetadata()['log'], 2);
    }

    /**
     * @param $channel
     *
     * @return ChannelPreferences
     */
    private function getChannelPreference($channel, Event $event)
    {
        return new ChannelPreferences($event);
    }
}
