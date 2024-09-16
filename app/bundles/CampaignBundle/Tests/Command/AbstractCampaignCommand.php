<?php

namespace Mautic\CampaignBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Lead;

class AbstractCampaignCommand extends MauticMysqlTestCase
{
    /**
     * @var array
     */
    protected $defaultClientServer = [];

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var \DateTime
     */
    protected $eventDate;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        // Everything needs to happen anonymously
        $this->defaultClientServer = $this->clientServer;
        $this->clientServer        = [];

        parent::setUp();

        $this->db     = $this->em->getConnection();
        $this->prefix = self::$container->getParameter('mautic.db_table_prefix');

        // Populate contacts
        $this->installDatabaseFixtures([LeadFieldData::class, LoadLeadData::class]);

        // Campaigns are so complex that we are going to load a SQL file rather than build with entities
        $sql = file_get_contents(__DIR__.'/campaign_schema.sql');

        // Update table prefix
        $sql = str_replace('#__', self::$container->getParameter('mautic.db_table_prefix'), $sql);

        // Schedule event
        date_default_timezone_set('UTC');
        $this->eventDate = new \DateTime();
        $this->eventDate->modify('+3 seconds');
        $sql = str_replace('{SEND_EMAIL_1_TIMESTAMP}', $this->eventDate->format('Y-m-d H:i:s'), $sql);

        $this->eventDate->modify('+6 seconds');
        $sql = str_replace('{CONDITION_TIMESTAMP}', $this->eventDate->format('Y-m-d H:i:s'), $sql);

        $this->em->getConnection()->exec($sql);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->clientServer = $this->defaultClientServer;
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'emails',
            'lead_tags',
            'campaigns',
            'campaign_events',
            'lead_lists',
        ]);
    }

    /**
     * @return array
     */
    protected function getCampaignEventLogs(array $ids)
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

    protected function createLead(string $leadName): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($leadName);
        $this->em->persist($lead);

        return $lead;
    }

    protected function createCampaign(string $campaignName): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($campaignName);
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        return $campaign;
    }

    protected function createCampaignLead(Campaign $campaign, Lead $lead): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);

        return $campaignLead;
    }

    protected function createEvent(string $name, Campaign $campaign, string $type, string $eventType, array $property = null): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType($type);
        $event->setEventType($eventType);
        $event->setTriggerInterval(1);
        $event->setProperties($property);
        $event->setTriggerMode('immediate');
        $this->em->persist($event);

        return $event;
    }

    protected function createEventLog(Lead $lead, Event $event, Campaign $campaign): LeadEventLog
    {
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $leadEventLog->setCampaign($campaign);
        $leadEventLog->setRotation(0);
        $this->em->persist($leadEventLog);

        return $leadEventLog;
    }
}
