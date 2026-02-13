<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Enum\RepublishBehavior;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CampaignBundle\Tests\CampaignAuditLogTrait;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Command\SegmentCountCacheCommand;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use PHPUnit\Framework\Assert;

class TriggerCampaignCommandTest extends AbstractCampaignCommand
{
    use CampaignAuditLogTrait;
    private ?SegmentCountCacheHelper $segmentCountCacheHelper = null;

    protected function setUp(): void
    {
        $this->configParams['update_segment_contact_count_in_background'] = 'testSegmentCacheCountInBackground' === $this->name();
        parent::setUp();

        putenv('CAMPAIGN_EXECUTIONER_SCHEDULER_ACKNOWLEDGE_SECONDS=1');

        $this->segmentCountCacheHelper = static::getContainer()->get('mautic.helper.segment.count.cache');
    }

    public function beforeTearDown(): void
    {
        parent::beforeTearDown();

        putenv('CAMPAIGN_EXECUTIONER_SCHEDULER_ACKNOWLEDGE_SECONDS=0');

        $this->segmentCountCacheHelper = null;
    }

    /**
     * @throws \Exception
     */
    public function testCampaignExecutionForAll(): void
    {
        // Process in batches of 10 to ensure batching is working as expected
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '-l' => 10]);

        // Let's analyze
        $byEvent = $this->getCampaignEventLogs([1, 2, 11, 12, 13, 16]);
        $tags    = $this->getTagCounts();

        // Everyone should have been tagged with CampaignTest and have been sent Campaign Test Email 1
        $this->assertCount(50, $byEvent[1]);
        $this->assertCount(50, $byEvent[2]);

        // Sending Campaign Test Email 1 should be scheduled
        foreach ($byEvent[2] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Sending Campaign Test Email 1 was not scheduled for lead ID '.$log['lead_id']);
            }
        }

        // Everyone should have had the Is US condition processed
        $this->assertCount(50, $byEvent[11]);

        // 42 should have been send down the non-action path (red) of the condition
        $nonActionCount = $this->getNonActionPathTakenCount($byEvent[11]);
        $this->assertEquals(42, $nonActionCount);

        // 8 contacts are from the US and should be labeled with US:Action
        $this->assertCount(8, $byEvent[12]);
        $this->assertEquals(8, $tags['US:Action']);

        // Those tagged with US:Action should also be tagged with ChainedAction by a chained event.
        $this->assertCount(8, $byEvent[16]);
        $this->assertEquals(8, $tags['ChainedAction']);

        // The rest (42) contacts are not from the US and should be labeled with NonUS:Action
        $this->assertCount(42, $byEvent[13]);
        $this->assertEquals(42, $tags['NonUS:Action']);

        // No emails should be sent till after 5 seconds and the command is ran again
        $stats = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'email_stats', 'stat')
            ->where('stat.lead_id <= 25')
            ->executeQuery()
            ->fetchAllAssociative();
        $this->assertCount(0, $stats);

        // Wait 6 seconds then execute the campaign again to send scheduled events
        static::getContainer()->get(ScheduledExecutioner::class)->setNowTime(new \DateTime('+'.self::CONDITION_SECONDS.' seconds'));
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '-l' => 10]);

        // Send email 1 should no longer be scheduled
        $byEvent = $this->getCampaignEventLogs([2, 4]);
        $this->assertCount(50, $byEvent[2]);
        foreach ($byEvent[2] as $log) {
            if (1 === (int) $log['is_scheduled']) {
                $this->fail('Sending Campaign Test Email 1 is still scheduled for lead ID '.$log['lead_id']);
            }
        }

        // The non-action events attached to the decision should have no logs entries
        $this->assertCount(0, $byEvent[4]);

        // Check that the emails actually sent
        $stats = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'email_stats', 'stat')
            ->where('stat.lead_id <= 25')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->assertCount(25, $stats);

        // Now let's simulate email opens
        foreach ($stats as $stat) {
            $this->client->request('GET', '/email/'.$stat['tracking_hash'].'.gif');
            $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), var_export($this->client->getResponse()->getContent(), true));
        }

        $byEvent = $this->getCampaignEventLogs([3, 4, 5, 10, 14, 15]);

        // The non-action events attached to the decision should have no logs entries
        $this->assertCount(0, $byEvent[4]);
        $this->assertCount(0, $byEvent[5]);
        $this->assertCount(0, $byEvent[14]);
        $this->assertCount(0, $byEvent[15]);

        // Those 25 should now have open email decisions logged and the next email sent
        $this->assertCount(25, $byEvent[3]);
        $this->assertCount(25, $byEvent[10]);

        // Wait another 6 seconds to go beyond the inaction timeframe
        static::getContainer()->get(InactiveExecutioner::class)->setNowTime(new \DateTime('+'.(self::CONDITION_SECONDS * 2).' seconds'));

        // Execute the command again to trigger inaction related events
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '-l' => 10]);

        // Now we should have 50 email open decisions
        $byEvent = $this->getCampaignEventLogs([3, 4, 5, 14, 15]);
        $this->assertCount(50, $byEvent[3]);

        // 25 should be marked as non_action_path_taken
        $nonActionCount = $this->getNonActionPathTakenCount($byEvent[3]);
        $this->assertEquals(25, $nonActionCount);

        // A condition should be logged as evaluated for each of the 25 contacts
        $this->assertCount(25, $byEvent[4]);
        $this->assertCount(25, $byEvent[5]);

        // Tag EmailNotOpen should all be scheduled for these 25 contacts because the condition's timeframe was shorter and therefore the
        // contact was sent down the inaction path
        $this->assertCount(25, $byEvent[14]);
        $this->assertCount(25, $byEvent[15]);

        $utcTimezone = new \DateTimeZone('UTC');
        foreach ($byEvent[14] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Tag EmailNotOpen is not scheduled for lead ID '.$log['lead_id']);
            }

            $scheduledFor = new \DateTime($log['trigger_date'], $utcTimezone);
            $diff         = $this->eventDate->diff($scheduledFor);

            if (2 !== $diff->i) {
                $this->fail('Tag EmailNotOpen should be scheduled for around 2 minutes ('.$diff->i.' minutes)');
            }
        }

        foreach ($byEvent[15] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Tag EmailNotOpen Again is not scheduled for lead ID '.$log['lead_id']);
            }

            $scheduledFor = new \DateTime($log['trigger_date'], $utcTimezone);
            $diff         = $this->eventDate->diff($scheduledFor);

            if (6 !== $diff->i) {
                $this->fail('Tag EmailNotOpen Again should be scheduled for around 6 minutes ('.$diff->i.' minutes)');
            }
        }
        $byEvent = $this->getCampaignEventLogs([6, 7, 8, 9]);
        $tags    = $this->getTagCounts();

        // Of those that did not open the email, 6 should be tagged US:NotOpen
        $this->assertCount(6, $byEvent[6]);
        $this->assertEquals(6, $tags['US:NotOpen']);

        // And 19 should be tagged NonUS:NotOpen
        $this->assertCount(19, $byEvent[7]);
        $this->assertEquals(19, $tags['NonUS:NotOpen']);

        // And 4 should be tagged UK:NotOpen
        $this->assertCount(4, $byEvent[8]);
        $this->assertEquals(4, $tags['UK:NotOpen']);

        // And 21 should be tagged NonUK:NotOpen
        $this->assertCount(21, $byEvent[9]);
        $this->assertEquals(21, $tags['NonUK:NotOpen']);

        // No one should be tagged as EmailNotOpen because the actions are still scheduled
        $this->assertFalse(isset($tags['EmailNotOpen']));
    }

    /**
     * @throws \Exception
     */
    public function testCampaignExecutionForOne(): void
    {
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-id' => 1]);

        // Let's analyze
        $byEvent = $this->getCampaignEventLogs([1, 2, 11, 12, 13, 16]);
        $tags    = $this->getTagCounts();

        // Everyone should have been tagged with CampaignTest and have been sent Campaign Test Email 1
        $this->assertCount(1, $byEvent[1]);
        $this->assertCount(1, $byEvent[2]);

        // Sending Campaign Test Email 1 should be scheduled
        foreach ($byEvent[2] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Sending Campaign Test Email 1 was not scheduled for lead ID '.$log['lead_id']);
            }
        }

        // Everyone should have had the Is US condition processed
        $this->assertCount(1, $byEvent[11]);

        // 1 should have been send down the non-action path (red) of the condition
        $nonActionCount = $this->getNonActionPathTakenCount($byEvent[11]);
        $this->assertEquals(1, $nonActionCount);

        // 0 contacts are from the US and should be labeled with US:Action
        $this->assertCount(0, $byEvent[12]);
        $this->assertTrue(empty($tags['US:Action']));

        // None tagged with US:Action, so none should be tagged with ChainedAction by a chained event.
        $this->assertCount(0, $byEvent[16]);
        $this->assertTrue(empty($tags['ChainedAction']));

        // The rest (1) contacts are not from the US and should be labeled with NonUS:Action
        $this->assertCount(1, $byEvent[13]);
        $this->assertEquals(1, $tags['NonUS:Action']);

        // No emails should be sent till after 5 seconds and the command is ran again
        $stats = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'email_stats', 'stat')
            ->where('stat.lead_id = 1')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->assertCount(0, $stats);

        // Wait 6 seconds then execute the campaign again to send scheduled events
        static::getContainer()->get(ScheduledExecutioner::class)->setNowTime(new \DateTime('+'.self::CONDITION_SECONDS.' seconds'));
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-id' => 1]);

        // Send email 1 should no longer be scheduled
        $byEvent = $this->getCampaignEventLogs([2, 4]);
        $this->assertCount(1, $byEvent[2]);
        foreach ($byEvent[2] as $log) {
            if (1 === (int) $log['is_scheduled']) {
                $this->fail('Sending Campaign Test Email 1 is still scheduled for lead ID '.$log['lead_id']);
            }
        }

        // The non-action events attached to the decision should have no logs entries
        $this->assertCount(0, $byEvent[4]);

        // Check that the emails actually sent
        $stats = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'email_stats', 'stat')
            ->where('stat.lead_id = 1')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->assertCount(1, $stats);

        // Now let's simulate email opens
        foreach ($stats as $stat) {
            $this->client->request('GET', '/email/'.$stat['tracking_hash'].'.gif');
            $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), var_export($this->client->getResponse()->getContent(), true));
        }

        $byEvent = $this->getCampaignEventLogs([3, 4, 5, 10, 14, 15]);

        // The non-action events attached to the decision should have no logs entries
        $this->assertCount(0, $byEvent[4]);
        $this->assertCount(0, $byEvent[5]);
        $this->assertCount(0, $byEvent[14]);
        $this->assertCount(0, $byEvent[15]);

        // The 1 should now have open email decisions logged and the next email sent
        $this->assertCount(1, $byEvent[3]);
        $this->assertCount(1, $byEvent[10]);

        // Wait 6 seconds to go beyond the inaction timeframe
        static::getContainer()->get(InactiveExecutioner::class)->setNowTime(new \DateTime('+'.(self::CONDITION_SECONDS * 2).' seconds'));

        // Execute the command again to trigger inaction related events
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-id' => 1]);

        // Now we should have 1 email open decisions
        $byEvent = $this->getCampaignEventLogs([3, 4, 5, 14, 15]);
        $this->assertCount(1, $byEvent[3]);

        // 0 should be marked as non_action_path_taken
        $nonActionCount = $this->getNonActionPathTakenCount($byEvent[3]);
        $this->assertEquals(0, $nonActionCount);

        // There should be no inactive events
        $this->assertCount(0, $byEvent[4]);
        $this->assertCount(0, $byEvent[5]);
        $this->assertCount(0, $byEvent[14]);
        $this->assertCount(0, $byEvent[15]);

        $utcTimezone = new \DateTimeZone('UTC');
        foreach ($byEvent[14] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Tag EmailNotOpen is not scheduled for lead ID '.$log['lead_id']);
            }

            $scheduledFor = new \DateTime($log['trigger_date'], $utcTimezone);
            $diff         = $this->eventDate->diff($scheduledFor);

            if (2 !== $diff->i) {
                $this->fail('Tag EmailNotOpen should be scheduled for around 2 minutes ('.$diff->i.' minutes)');
            }
        }

        foreach ($byEvent[15] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Tag EmailNotOpen Again is not scheduled for lead ID '.$log['lead_id']);
            }

            $scheduledFor = new \DateTime($log['trigger_date'], $utcTimezone);
            $diff         = $this->eventDate->diff($scheduledFor);

            if (6 !== $diff->i) {
                $this->fail('Tag EmailNotOpen Again should be scheduled for around 6 minutes ('.$diff->i.' minutes)');
            }
        }
        $byEvent = $this->getCampaignEventLogs([6, 7, 8, 9]);
        $tags    = $this->getTagCounts();

        // Of those that did not open the email, 0 should be tagged US:NotOpen
        $this->assertCount(0, $byEvent[6]);
        $this->assertTrue(empty($tags['US:NotOpen']));

        // And 0 should be tagged NonUS:NotOpen
        $this->assertCount(0, $byEvent[7]);
        $this->assertTrue(empty($tags['NonUS:NotOpen']));

        // And 0 should be tagged UK:NotOpen
        $this->assertCount(0, $byEvent[8]);
        $this->assertTrue(empty($tags['UK:NotOpen']));

        // And 0 should be tagged NonUK:NotOpen
        $this->assertCount(0, $byEvent[9]);
        $this->assertTrue(empty($tags['NonUK:NotOpen']));

        // No one should be tagged as EmailNotOpen because the actions are still scheduled
        $this->assertTrue(empty($tags['EmailNotOpen']));
    }

    public function testCampaignExecutionForSome(): void
    {
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3,4,19']);

        // Let's analyze
        $byEvent = $this->getCampaignEventLogs([1, 2, 11, 12, 13, 16]);
        $tags    = $this->getTagCounts();

        // Everyone should have been tagged with CampaignTest and have been sent Campaign Test Email 1
        $this->assertCount(5, $byEvent[1]);
        $this->assertCount(5, $byEvent[2]);

        // Sending Campaign Test Email 1 should be scheduled
        foreach ($byEvent[2] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Sending Campaign Test Email 1 was not scheduled for lead ID '.$log['lead_id']);
            }
        }

        // Everyone should have had the Is US condition processed
        $this->assertCount(5, $byEvent[11]);

        // 4 should have been send down the non-action path (red) of the condition
        $nonActionCount = $this->getNonActionPathTakenCount($byEvent[11]);
        $this->assertEquals(4, $nonActionCount);

        // 1 contacts are from the US and should be labeled with US:Action
        $this->assertCount(1, $byEvent[12]);
        $this->assertEquals(1, $tags['US:Action']);

        // Those tagged with US:Action should also be tagged with ChainedAction by a chained event.
        $this->assertCount(1, $byEvent[16]);
        $this->assertEquals(1, $tags['ChainedAction']);

        // The rest (4) contacts are not from the US and should be labeled with NonUS:Action
        $this->assertCount(4, $byEvent[13]);
        $this->assertEquals(4, $tags['NonUS:Action']);

        // No emails should be sent till after 5 seconds and the command is ran again
        $stats = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'email_stats', 'stat')
            ->where('stat.lead_id <= 2')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->assertCount(0, $stats);

        // Wait 6 seconds then execute the campaign again to send scheduled events
        static::getContainer()->get(ScheduledExecutioner::class)->setNowTime(new \DateTime('+'.self::CONDITION_SECONDS.' seconds'));
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3,4,19']);

        // Send email 1 should no longer be scheduled
        $byEvent = $this->getCampaignEventLogs([2, 4]);
        $this->assertCount(5, $byEvent[2]);
        foreach ($byEvent[2] as $log) {
            if (1 === (int) $log['is_scheduled']) {
                $this->fail('Sending Campaign Test Email 1 is still scheduled for lead ID '.$log['lead_id']);
            }
        }

        // The non-action events attached to the decision should have no logs entries
        $this->assertCount(0, $byEvent[4]);

        // Check that the emails actually sent
        $stats = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'email_stats', 'stat')
            ->where('stat.lead_id <= 2')
            ->executeQuery()
            ->fetchAllAssociative();
        $this->assertCount(2, $stats);

        // Now let's simulate email opens
        foreach ($stats as $stat) {
            $this->client->request('GET', '/email/'.$stat['tracking_hash'].'.gif');
            $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), var_export($this->client->getResponse()->getContent(), true));
        }

        $byEvent = $this->getCampaignEventLogs([3, 4, 5, 10, 14, 15]);

        // The non-action events attached to the decision should have no logs entries
        $this->assertCount(0, $byEvent[4]);
        $this->assertCount(0, $byEvent[5]);
        $this->assertCount(0, $byEvent[14]);
        $this->assertCount(0, $byEvent[15]);

        // Those 25 should now have open email decisions logged and the next email sent
        $this->assertCount(2, $byEvent[3]);
        $this->assertCount(2, $byEvent[10]);

        // Wait 6 seconds to go beyond the inaction timeframe
        static::getContainer()->get(InactiveExecutioner::class)->setNowTime(new \DateTime('+'.(self::CONDITION_SECONDS * 2).' seconds'));

        // Execute the command again to trigger inaction related events
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3,4,19']);

        // Now we should have 5 email open decisions
        $byEvent = $this->getCampaignEventLogs([3, 4, 5, 14, 15]);
        $this->assertCount(5, $byEvent[3]);

        // 3 should be marked as non_action_path_taken
        $nonActionCount = $this->getNonActionPathTakenCount($byEvent[3]);
        $this->assertEquals(3, $nonActionCount);

        // A condition should be logged as evaluated for each of the 3 contacts
        $this->assertCount(3, $byEvent[4]);
        $this->assertCount(3, $byEvent[5]);

        // Tag EmailNotOpen should all be scheduled for these 3 contacts because the condition's timeframe was shorter and therefore the
        // contact was sent down the inaction path
        $this->assertCount(3, $byEvent[14]);
        $this->assertCount(3, $byEvent[15]);

        $utcTimezone = new \DateTimeZone('UTC');
        foreach ($byEvent[14] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Tag EmailNotOpen is not scheduled for lead ID '.$log['lead_id']);
            }

            $scheduledFor = new \DateTime($log['trigger_date'], $utcTimezone);
            $diff         = $this->eventDate->diff($scheduledFor);

            if (2 !== $diff->i) {
                $this->fail('Tag EmailNotOpen should be scheduled for around 2 minutes ('.$diff->i.' minutes)');
            }
        }

        foreach ($byEvent[15] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Tag EmailNotOpen Again is not scheduled for lead ID '.$log['lead_id']);
            }

            $scheduledFor = new \DateTime($log['trigger_date'], $utcTimezone);
            $diff         = $this->eventDate->diff($scheduledFor);

            if (6 !== $diff->i) {
                $this->fail('Tag EmailNotOpen Again should be scheduled for around 6 minutes ('.$diff->i.' minutes)');
            }
        }
        $byEvent = $this->getCampaignEventLogs([6, 7, 8, 9]);
        $tags    = $this->getTagCounts();

        // Of those that did not open the email, 1 should be tagged US:NotOpen
        $this->assertCount(1, $byEvent[6]);
        $this->assertEquals(1, $tags['US:NotOpen']);

        // And 2 should be tagged NonUS:NotOpen
        $this->assertCount(2, $byEvent[7]);
        $this->assertEquals(2, $tags['NonUS:NotOpen']);

        // And 2 should be tagged UK:NotOpen
        $this->assertCount(2, $byEvent[8]);
        $this->assertEquals(2, $tags['UK:NotOpen']);

        // And 1 should be tagged NonUK:NotOpen
        $this->assertCount(1, $byEvent[9]);
        $this->assertEquals(1, $tags['NonUK:NotOpen']);

        // No one should be tagged as EmailNotOpen because the actions are still scheduled
        $this->assertFalse(isset($tags['EmailNotOpen']));
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function testSegmentCacheCountInBackground(): void
    {
        // remove redis key if exist
        $this->segmentCountCacheHelper->deleteSegmentContactCount(1);

        // Execute the command again to trigger related events.
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1]);

        $count = $this->segmentCountCacheHelper->getSegmentContactCount(1);
        self::assertEquals(0, $count);

        $this->testSymfonyCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Segment cache count should be 50.
        $count = $this->segmentCountCacheHelper->getSegmentContactCount(1);
        self::assertEquals(50, $count);
    }

    public function testCampaignActionChangeMembership(): void
    {
        $campaign1 = $this->createCampaign('Campaign 1');
        $campaign2 = $this->createCampaign('Campaign 2');
        $lead      = $this->createLead('Lead');
        $this->createCampaignLead($campaign1, $lead);
        $this->em->flush();
        $property = ['addTo' => [$campaign2->getId()], 'removeFrom' => ['this']];
        $this->createEvent('Event', $campaign1, 'campaign.addremovelead', 'action', $property);
        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign1->getId(), '--contact-id' => $lead->getId(), '--kickoff-only' => true]);

        $campaignLeads = $this->em->getRepository(Lead::class)->findBy(['lead' => $lead], ['campaign' => 'ASC']);

        Assert::assertCount(2, $campaignLeads);
        Assert::assertSame($campaign1->getId(), $campaignLeads[0]->getCampaign()->getId());
        Assert::assertTrue($campaignLeads[0]->getManuallyRemoved());
        Assert::assertSame($campaign2->getId(), $campaignLeads[1]->getCampaign()->getId());
        Assert::assertFalse($campaignLeads[1]->getManuallyRemoved());
    }

    public function testCampaignActionChangeMembershipRestartRotation(): void
    {
        $campaign1 = $this->createCampaign('Campaign 1');
        // create campaign with restart allowed
        $campaign2 = $this->createCampaign('Campaign 2');
        $campaign2->setAllowRestart(true);
        $this->em->persist($campaign2);

        $lead      = $this->createLead('Lead');

        // add lead to both campaigns
        $this->createCampaignLead($campaign1, $lead);
        $this->createCampaignLead($campaign2, $lead);
        $this->em->flush();

        // add action changeCampaigns to add the lead again to campaign2
        $property = ['addTo' => [$campaign2->getId()], 'removeFrom' => ['this']];
        $this->createEvent('Event', $campaign1, 'campaign.addremovelead', 'action', $property);
        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign1->getId(), '--contact-id' => $lead->getId(), '--kickoff-only' => true]);

        $campaignLeads = $this->em->getRepository(Lead::class)->findBy(['lead' => $lead], ['campaign' => 'ASC']);

        Assert::assertCount(2, $campaignLeads);
        Assert::assertSame($campaign1->getId(), $campaignLeads[0]->getCampaign()->getId());
        Assert::assertTrue($campaignLeads[0]->getManuallyRemoved());
        Assert::assertSame($campaign2->getId(), $campaignLeads[1]->getCampaign()->getId());
        Assert::assertFalse($campaignLeads[1]->getManuallyRemoved());
        Assert::assertSame(2, $campaignLeads[1]->getRotation()); // assert it's the second rotation
    }

    public function testCampaignActionAfterChangeMembership(): void
    {
        $campaign  = $this->createCampaign('Campaign 1');
        $lead      = $this->createLead('Lead');
        $this->createCampaignLead($campaign, $lead);
        $this->em->flush();
        $property = ['removeFrom' => ['this']];
        $event1   = $this->createEvent('Event', $campaign, 'campaign.addremovelead', 'action', $property);
        $property = ['points' => 1];
        $event2   = $this->createEvent('Event', $campaign, 'lead.changepoints', 'action', $property);
        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId(), '--contact-id' => $lead->getId(), '--kickoff-only' => true]);

        $campaignLeads = $this->em->getRepository(Lead::class)->findBy(['lead' => $lead]);
        Assert::assertCount(1, $campaignLeads);
        Assert::assertSame($campaign->getId(), $campaignLeads[0]->getCampaign()->getId());
        Assert::assertTrue($campaignLeads[0]->getManuallyRemoved());

        $campaignEventLogs = $this->em->getRepository(LeadEventLog::class)->findBy(['campaign' => $campaign, 'lead' => $lead], ['event' => 'ASC']);
        Assert::assertCount(1, $campaignEventLogs);
        Assert::assertSame($campaign->getId(), $campaignEventLogs[0]->getCampaign()->getId());
        Assert::assertSame($event1->getId(), $campaignEventLogs[0]->getEvent()->getId());
    }

    public function testCampaignActionBeforeChangeMembership(): void
    {
        $campaign  = $this->createCampaign('Campaign 1');
        $lead      = $this->createLead('Lead');
        $this->createCampaignLead($campaign, $lead);
        $this->em->flush();
        $property = ['points' => 1];
        $event1   = $this->createEvent('Event', $campaign, 'lead.changepoints', 'action', $property);
        $property = ['removeFrom' => ['this']];
        $event2   = $this->createEvent('Event', $campaign, 'campaign.addremovelead', 'action', $property);
        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId(), '--contact-id' => $lead->getId(), '--kickoff-only' => true]);

        $campaignLeads = $this->em->getRepository(Lead::class)->findBy(['lead' => $lead]);
        Assert::assertCount(1, $campaignLeads);
        Assert::assertSame($campaign->getId(), $campaignLeads[0]->getCampaign()->getId());
        Assert::assertTrue($campaignLeads[0]->getManuallyRemoved());

        $campaignEventLogs = $this->em->getRepository(LeadEventLog::class)->findBy(['campaign' => $campaign, 'lead' => $lead], ['event' => 'ASC']);
        Assert::assertCount(2, $campaignEventLogs);
        Assert::assertSame($campaign->getId(), $campaignEventLogs[0]->getCampaign()->getId());
        Assert::assertSame($event1->getId(), $campaignEventLogs[0]->getEvent()->getId());
        Assert::assertSame($campaign->getId(), $campaignEventLogs[1]->getCampaign()->getId());
        Assert::assertSame($event2->getId(), $campaignEventLogs[1]->getEvent()->getId());
    }

    public function testCampaignExclusion(): void
    {
        $campaign1 = $this->createCampaign('Campaign 1');
        $campaign2 = $this->createCampaign('Campaign 2');
        $lead      = $this->createLead('Lead');
        $this->createCampaignLead($campaign1, $lead);
        $this->createCampaignLead($campaign2, $lead);
        $this->em->flush();
        $property = ['addTo' => [$campaign2->getId()], 'removeFrom' => ['this']];
        $this->createEvent('Event', $campaign1, 'campaign.addremovelead', 'action', $property);
        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--exclude' => [$campaign1->getId()], '--contact-id' => $lead->getId(), '--kickoff-only' => true]);

        $campaignLeads = $this->em->getRepository(Lead::class)->findBy(['lead' => $lead], ['campaign' => 'ASC']);

        Assert::assertCount(2, $campaignLeads);
        Assert::assertSame($campaign1->getId(), $campaignLeads[0]->getCampaign()->getId());
        Assert::assertFalse($campaignLeads[0]->getManuallyRemoved(), 'Test not executed campaign does not have Contact removed.');
        Assert::assertSame($campaign2->getId(), $campaignLeads[1]->getCampaign()->getId());
        Assert::assertFalse($campaignLeads[1]->getManuallyRemoved());
        Assert::assertFalse($campaignLeads[1]->getManuallyAdded());
    }

    /**
     * @see https://github.com/mautic/mautic/issues/11061
     *
     * This test will not fail if the infinite loop returns and instead run indefinitelly until a PHPUNIT timeout is reached.
     * I couldn't find an easy way to test for an infinite loop. But we'll know if it returns again.
     * We'll just spend more time figuring out which test is taking so long.
     */
    public function testCampaignInfiniteLoop(): void
    {
        $campaignMemberRepo = $this->em->getRepository(Lead::class);

        $segmentMemberRepo = $this->em->getRepository(ListLead::class);

        $campaignRepo = $this->em->getRepository(Campaign::class);

        // Clear the campaign and segment members as those are manually_added.
        $campaignMemberRepo->deleteEntities($campaignMemberRepo->findAll());
        $segmentMemberRepo->deleteEntities($segmentMemberRepo->findAll());

        $campaign = $campaignRepo->find(1); // Created in parent::setUp()
        \assert($campaign instanceof Campaign);

        $campaign->setAllowRestart(true);

        $campaignRepo->saveEntity($campaign);

        $john = $this->createLead('John');
        $jane = $this->createLead('Jane');
        $this->createSegmentMember($campaign->getLists()->first(), $john);
        $this->createSegmentMember($campaign->getLists()->first(), $jane);
        $this->createCampaignLead($campaign, $john);
        $this->createCampaignLead($campaign, $jane, true); // Manually removed.
        $this->em->flush();
        $this->em->detach($campaign);
        $this->em->detach($campaignRepo);

        $tStart = microtime(true);

        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $campaign->getId()]);

        $tDiff = microtime(true) - $tStart;

        $this->assertLessThan(10, $tDiff, 'The campaign rebuild takes more than 10 seconds, probably an infinite loop.');
    }

    public function testCampaignExecuteOrderByDateCreatedDesc(): void
    {
        $oldCampaign = $this->createCampaign('Some old campaign');
        $oldCampaign->setDateAdded(new \DateTime('2019-01-03 03:54:25'));
        $newCampaign = $this->createCampaign('New campaign');
        $newCampaign->setDateAdded(new \DateTime());
        $this->em->flush();

        $commandTester = $this->testSymfonyCommand('mautic:campaigns:trigger');
        $commandTester->assertCommandIsSuccessful();
        $lines = preg_split('/\r\n|\r|\n/', $commandTester->getDisplay());

        $campaignStartLines = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, 'Triggering events for campaign')) {
                $campaignStartLines[] = $line;
            }
        }

        // check if the new campaign processed first
        $this->assertEquals("Triggering events for campaign {$newCampaign->getId()}", $campaignStartLines[0]);
    }

    /**
     * @throws \Exception
     */
    public function testSegmentCacheCount(): void
    {
        // Execute the command again to trigger related events.
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1]);
        // Segment cache count should be 50.
        $count = $this->segmentCountCacheHelper->getSegmentContactCount(1);
        self::assertEquals(50, $count);
    }

    /**
     * @return array
     */
    private function getTagCounts()
    {
        $tags = $this->db->createQueryBuilder()
            ->select('t.tag, count(*) as the_count')
            ->from($this->prefix.'lead_tags', 't')
            ->join('t', $this->prefix.'lead_tags_xref', 'l', 't.id = l.tag_id')
            ->groupBy('t.tag')
            ->executeQuery()
            ->fetchAllAssociative();

        $tagCounts = [];
        foreach ($tags as $tag) {
            $tagCounts[$tag['tag']] = (int) $tag['the_count'];
        }

        return $tagCounts;
    }

    /**
     * @return int
     */
    private function getNonActionPathTakenCount(array $logs)
    {
        $nonActionCount = 0;
        foreach ($logs as $log) {
            if ((int) $log['non_action_path_taken']) {
                ++$nonActionCount;
            }
        }

        return $nonActionCount;
    }

    /**
     * @param array<array{dateAdded: string, details: array<string, array<int, mixed>>}> $auditLogs
     * @param array<int, array<string, string>>                                          $expectedTriggerDateLog
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('republishBehaviorProvider')]
    public function testTriggerCampaignCommandWithRepublishBehavior(
        string $republishBehavior,
        string $triggerMode,
        int $intervalDays,
        array $auditLogs,
        string $expectedTriggerDate,
        bool $expectedIsScheduled,
        array $expectedTriggerDateLog,
    ): void {
        // Create an unpublished campaign with specified republish behavior
        $campaign = new Campaign();
        $campaign->setName("Campaign with {$republishBehavior} behavior");
        $campaign->setIsPublished(true);
        $campaign->setRepublishBehavior($republishBehavior);

        $this->em->persist($campaign);

        // Create an interval event
        $event = new Event();
        $event->setName('Test Interval Event');
        $event->setType('email.send');
        $event->setCampaign($campaign);
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode($triggerMode);
        $event->setTriggerInterval($intervalDays);
        $event->setTriggerIntervalUnit('d');

        $this->em->persist($event);

        // Create a contact
        $contact = new Contact();
        $contact->setEmail("test-{$republishBehavior}@example.com");

        $this->em->persist($contact);

        // Create an event log with past trigger date
        $eventLog = new LeadEventLog();
        $eventLog->setEvent($event);
        $eventLog->setCampaign($campaign);
        $eventLog->setLead($contact);
        $eventLog->setTriggerDate(new \DateTime('2024-10-12 00:00:00'), 'Test setup'); // dateTriggered + 10 days
        $eventLog->setDateTriggered(new \DateTime('2024-10-02 00:00:00')); // = date created for a new log
        $eventLog->setIsScheduled(true);

        $this->em->persist($eventLog);
        $this->em->flush();

        $this->saveAuditLogs($this->em, $auditLogs, $campaign);

        $this->em->clear();

        // Execute using the command
        $logId         = $eventLog->getId();
        $commandTester = $this->testSymfonyCommand('mautic:campaigns:trigger', [
            '--campaign-id'       => $campaign->getId(),
            '--scheduled-only'    => true, // Only execute scheduled events to test the executeScheduled method
            '--contact-id'        => $contact->getId(),
        ]);

        $this->assertSame(0, $commandTester->getStatusCode(), $commandTester->getDisplay());

        $eventLog = $this->em->find(LeadEventLog::class, $logId);
        \assert($eventLog instanceof LeadEventLog);

        Assert::assertSame($expectedTriggerDate, $eventLog->getTriggerDate()?->format(DateTimeHelper::FORMAT_DB));
        Assert::assertSame($expectedIsScheduled, $eventLog->getIsScheduled());

        // Assert that trigger date logging is working
        $metadata = $eventLog->getMetadata();
        Assert::assertIsArray($metadata, 'Metadata should be an array');
        Assert::assertArrayHasKey('triggerDateLog', $metadata, 'Metadata should contain triggerDateLog');

        $triggerDateLog = $metadata['triggerDateLog'];
        Assert::assertNotEmpty($triggerDateLog, 'Trigger date log should contain entries');

        Assert::assertCount(count($expectedTriggerDateLog), $triggerDateLog);

        // Assert that the expected metadata is present
        foreach ($triggerDateLog as $key => $log) {
            Assert::assertSame($log['changedTo'], $expectedTriggerDateLog[$key]['changedTo']);
            Assert::assertSame($log['note'], $expectedTriggerDateLog[$key]['note']);
        }
    }

    /**
     * @return array<string, array{republishBehavior: string, triggerMode: string, intervalDays: int, auditLogs: array<array{dateAdded: string, details: array<string, array<int, mixed>>}>, expectedTriggerDate: string, expectedIsScheduled: bool}>
     */
    public static function republishBehaviorProvider(): array
    {
        $unpublishedAfter5days = 'published, unpublished and published';
        $auditLogs             = [
            $unpublishedAfter5days => [
                [
                    'dateAdded' => '2024-10-01 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
                [
                    'dateAdded' => '2024-10-05 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                    ],
                ],
                [
                    'dateAdded' => '2024-10-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
            ],
        ];

        return [
            'Same the original trigger date as it should not change' => [
                'republishBehavior'      => RepublishBehavior::COUNT_ALL_TIME->value,
                'triggerMode'            => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'           => 10,
                'auditLogs'              => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate'    => '2024-10-12 00:00:00', // Original trigger date
                'expectedIsScheduled'    => false, // Executed anyway as the trigger date is still in the past
                'expectedTriggerDateLog' => [
                    [
                        'changedTo' => '2024-10-12 00:00:00',
                        'note'      => 'Test setup',
                    ],
                ],
            ],
            '10 days after last publish which is 2024-10-10 00:00:00' => [
                'republishBehavior'      => RepublishBehavior::RESTART_ON_PUBLISH->value,
                'triggerMode'            => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'           => 10,
                'auditLogs'              => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate'    => '2024-10-20 00:00:00', // 10 days after last publish
                'expectedIsScheduled'    => false, // Executed anyway as the trigger date is still in the past
                'expectedTriggerDateLog' => [
                    [
                        'changedTo' => '2024-10-12 00:00:00',
                        'note'      => 'Test setup',
                    ],
                    [
                        'changedTo' => '2024-10-20 00:00:00',
                        'note'      => 'Campaign republish behavior: restart_on_publish',
                    ],
                ],
            ],
            'Scheduled at 2024-10-02 00:00:00 for 10 days (2024-10-12 00:00:00), unpublished at 2024-10-05 00:00:00 after 3 days, published 2024-10-10 00:00:00 (5 days)' => [
                'republishBehavior'      => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'            => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'           => 10,
                'auditLogs'              => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate'    => '2024-10-17 00:00:00', // 3 days were already published, 7 remaining to go after publish of 2024-10-10 00:00:00.
                'expectedIsScheduled'    => false, // Executed anyway as the trigger date is still in the past
                'expectedTriggerDateLog' => [
                    [
                        'changedTo' => '2024-10-12 00:00:00',
                        'note'      => 'Test setup',
                    ],
                    [
                        'changedTo' => '2024-10-17 00:00:00',
                        'note'      => 'Campaign republish behavior: count_only_while_published',
                    ],
                ],
            ],
            'Absolute date trigger mode should not do anything' => [
                'republishBehavior'      => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'            => Event::TRIGGER_MODE_DATE,
                'intervalDays'           => 10,
                'auditLogs'              => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate'    => '2024-10-12 00:00:00', // Original trigger date
                'expectedIsScheduled'    => false, // Executed anyway as the trigger date is still in the past
                'expectedTriggerDateLog' => [
                    [
                        'changedTo' => '2024-10-12 00:00:00',
                        'note'      => 'Test setup',
                    ],
                ],
            ],
            'Immediate trigger mode should not extend anything' => [
                'republishBehavior'      => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'            => Event::TRIGGER_MODE_IMMEDIATE,
                'intervalDays'           => 10,
                'auditLogs'              => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate'    => '2024-10-12 00:00:00', // Original trigger date
                'expectedIsScheduled'    => false, // Executed anyway as the trigger date is still in the past
                'expectedTriggerDateLog' => [
                    [
                        'changedTo' => '2024-10-12 00:00:00',
                        'note'      => 'Test setup',
                    ],
                ],
            ],
            'If the interval is empty then there will be no extension' => [
                'republishBehavior'      => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'            => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'           => 0,
                'auditLogs'              => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate'    => '2024-10-12 00:00:00', // Original trigger date
                'expectedIsScheduled'    => false, // Executed anyway as the trigger date is still in the past
                'expectedTriggerDateLog' => [
                    [
                        'changedTo' => '2024-10-12 00:00:00',
                        'note'      => 'Test setup',
                    ],
                ],
            ],
        ];
    }
}
