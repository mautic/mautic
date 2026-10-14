<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\PreferenceBuilder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\ChannelBundle\PreferenceBuilder\ChannelPreferences;
use Mautic\ChannelBundle\PreferenceBuilder\PreferenceBuilder;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
final class PreferenceBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testChannelsArePrioritized(): void
    {
        $lead = new Lead();
        $lead->setChannelRules(
            [
                'sms' => [
                    'dnc' => DoNotContact::IS_CONTACTABLE,
                ],
                'email' => [
                    'dnc' => DoNotContact::IS_CONTACTABLE,
                ],
            ]
        );

        $log = new LeadEventLog();
        $log->setLead($lead);
        ReflectionHelper::setEntityId($log, 1);

        $lead2 = new Lead();
        $lead2->setChannelRules(
            [
                'email' => [
                    'dnc' => DoNotContact::IS_CONTACTABLE,
                ],
                'sms' => [
                    'dnc' => DoNotContact::UNSUBSCRIBED,
                ],
            ]
        );

        $log2 = new LeadEventLog();
        $log2->setLead($lead2);
        ReflectionHelper::setEntityId($log2, 2);

        /** @var ArrayCollection<int, LeadEventLog> $logs */
        $logs = new ArrayCollection([$log, $log2]);

        $event = new Event();
        $event->setCampaign(new Campaign());

        $builder = new PreferenceBuilder($logs, $event, ['email' => [], 'sms' => [], 'push' => []], new NullLogger());

        $preferences = $builder->getChannelPreferences();

        $this->assertCount(3, $preferences);
        $this->assertArrayHasKey('email', $preferences);
        $this->assertArrayHasKey('sms', $preferences);
        $this->assertArrayHasKey('push', $preferences);

        /** @var ChannelPreferences $email */
        $email = $preferences['email'];

        // First priority
        $emailLogs = $email->getLogsByPriority(1);
        $this->assertCount(1, $emailLogs);
        $this->assertEquals(2, $emailLogs->first()->getId());

        // Second priority
        $emailLogs = $email->getLogsByPriority(2);
        $this->assertCount(1, $emailLogs);
        $this->assertEquals(1, $emailLogs->first()->getId());

        // First priority for SMS which should just be one
        /** @var ChannelPreferences $sms */
        $sms     = $preferences['sms'];
        $smsLogs = $sms->getLogsByPriority(1);
        $this->assertCount(1, $smsLogs);
        $this->assertEquals(1, $smsLogs->first()->getId());

        // None for second priority because of DNC
        $smsLogs = $sms->getLogsByPriority(2);
        $this->assertCount(0, $smsLogs);

        // No one had push enabled but it should be defined
        $push     = $preferences['push'];
        $pushLogs = $push->getLogsByPriority(1);
        $this->assertCount(0, $pushLogs);
    }

    public function testLogIsRemovedFromAllChannels(): void
    {
        $lead = new Lead();
        $lead->setChannelRules(
            [
                'sms' => [
                    'dnc' => DoNotContact::IS_CONTACTABLE,
                ],
                'email' => [
                    'dnc' => DoNotContact::IS_CONTACTABLE,
                ],
            ]
        );

        $log = new LeadEventLog();
        $log->setLead($lead);
        ReflectionHelper::setEntityId($log, 1);

        /** @var ArrayCollection<int, LeadEventLog> $logs */
        $logs = new ArrayCollection([$log]);

        $event   = new Event();
        $event->setCampaign(new Campaign());
        $builder = new PreferenceBuilder($logs, $event, ['email' => [], 'sms' => [], 'push' => []], new NullLogger());

        $preferences = $builder->getChannelPreferences();
        /** @var ChannelPreferences $sms */
        $sms     = $preferences['sms'];
        $smsLogs = $sms->getLogsByPriority(1);
        $this->assertCount(1, $smsLogs);

        /** @var ChannelPreferences $email */
        $email     = $preferences['email'];
        $emailLogs = $email->getLogsByPriority(2);
        $this->assertCount(1, $emailLogs);

        $builder->removeLogFromAllChannels($log);

        $preferences = $builder->getChannelPreferences();
        /** @var ChannelPreferences $sms */
        $sms     = $preferences['sms'];
        $smsLogs = $sms->getLogsByPriority(1);
        $this->assertCount(0, $smsLogs);

        /** @var ChannelPreferences $email */
        $email     = $preferences['email'];
        $emailLogs = $email->getLogsByPriority(2);
        $this->assertCount(0, $emailLogs);
    }
}
