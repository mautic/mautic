<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Mautic\CampaignBundle\Command\TriggerCampaignCommand;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class TriggerCampaignCommandTest extends MauticMysqlTestCase
{
    /**
     * @var array
     */
    private $defaultClientServer = [];

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var
     */
    private $prefix;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Everything needs to happen anonymously
        $this->defaultClientServer = $this->clientServer;
        $this->clientServer        = [];

        parent::setUp();

        $this->db     = $this->container->get('doctrine.dbal.default_connection');
        $this->prefix = $this->container->getParameter('mautic.db_table_prefix');

        // Populate contacts
        $this->installDatabaseFixtures([dirname(__DIR__).'/../../LeadBundle/DataFixtures/ORM/LoadLeadData.php']);

        // Campaigns are so complex that we are going to load a SQL file rather than build with entities
        $sql = file_get_contents(__DIR__.'/campaign_schema.sql');

        // Update table prefix
        $sql = str_replace('#__', $this->container->getParameter('mautic.db_table_prefix'), $sql);

        // Schedule event
        date_default_timezone_set('UTC');
        $event = new \DateTime();
        $event->modify('+15 seconds');
        $sql = str_replace('{SEND_EMAIL_1_TIMESTAMP}', $event->format('Y-m-d H:i:s'), $sql);

        $event->modify('+15 seconds');
        $sql = str_replace('{CONDITION_TIMESTAMP}', $event->format('Y-m-d H:i:s'), $sql);

        // Update the schema
        $tmpFile = $this->container->getParameter('kernel.cache_dir').'/campaign_schema.sql';
        file_put_contents($tmpFile, $sql);
        $this->applySqlFromFile($tmpFile);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->clientServer = $this->defaultClientServer;
    }

    /**
     * @throws \Exception
     */
    public function testCampaignExecution()
    {
        $command = new TriggerCampaignCommand();
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1], $command);

        // Let's analyze
        $byEvent = $this->getCampaignEventLogs([1, 2, 11, 12, 13]);
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

        // The rest (42) contacts are not from the US and should be labeled with NonUS:Action
        $this->assertCount(42, $byEvent[13]);
        $this->assertEquals(42, $tags['NonUS:Action']);

        // No emails should be sent till after 5 seconds and the command is ran again
        $stats = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'email_stats', 'stat')
            ->where('stat.lead_id <= 25')
            ->execute()
            ->fetchAll();
        $this->assertCount(0, $stats);

        // Wait 15 seconds then execute the campaign again to send scheduled events
        sleep(15);
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1], $command);

        // Send email 1 should no longer be scheduled
        $byEvent = $this->getCampaignEventLogs([2]);
        $this->assertCount(50, $byEvent[2]);
        foreach ($byEvent[2] as $log) {
            if (1 === (int) $log['is_scheduled']) {
                $this->fail('Sending Campaign Test Email 1 is still scheduled for lead ID '.$log['lead_id']);
            }
        }

        // Check that the emails actually sent
        $stats = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'email_stats', 'stat')
            ->where('stat.lead_id <= 25')
            ->execute()
            ->fetchAll();
        $this->assertCount(25, $stats);

        // Now let's simulate email opens
        foreach ($stats as $stat) {
            $this->client->request('GET', '/email/'.$stat['tracking_hash'].'.gif');
            $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        }

        // Those 25 should now have open email decisions logged and the next email sent
        $byEvent = $this->getCampaignEventLogs([3, 4, 5, 10, 14]);
        $this->assertCount(25, $byEvent[3]);
        $this->assertCount(25, $byEvent[10]);

        // The non-action events attached to the decision should have no logs entries
        $this->assertCount(0, $byEvent[4]);
        $this->assertCount(0, $byEvent[5]);
        $this->assertCount(0, $byEvent[14]);

        // Wait 15 seconds to go beyond the inaction timeframe
        sleep(15);

        // Execute the command again to trigger inaction related events
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1], $command);

        // Now we should have 50 email open decisions
        $byEvent = $this->getCampaignEventLogs([3, 4, 5, 14]);
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
        foreach ($byEvent[14] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Tag EmailNotOpen is not scheduled for lead ID '.$log['lead_id']);
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
     * @param array $ids
     *
     * @return array
     */
    private function getCampaignEventLogs(array $ids)
    {
        $logs = $this->db->createQueryBuilder()
            ->select('l.email, l.country, event.name, event.event_type, event.type, log.*')
            ->from($this->prefix.'campaign_lead_event_log', 'log')
            ->join('log', $this->prefix.'campaign_events', 'event', 'event.id = log.event_id')
            ->join('log', $this->prefix.'leads', 'l', 'l.id = log.lead_id')
            ->where('log.campaign_id = 1')
            ->andWhere('log.event_id IN ('.implode(',', $ids).')')
            ->execute()
            ->fetchAll();

        $byEvent = [];
        foreach ($ids as $id) {
            $byEvent[$id] = [];
        }

        foreach ($logs as $log) {
            $byEvent[$log['event_id']][] = $log;
        }

        return $byEvent;
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
            ->execute()
            ->fetchAll();

        $tagCounts = [];
        foreach ($tags as $tag) {
            $tagCounts[$tag['tag']] = (int) $tag['the_count'];
        }

        return $tagCounts;
    }

    /**
     * @param array $logs
     *
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
}
