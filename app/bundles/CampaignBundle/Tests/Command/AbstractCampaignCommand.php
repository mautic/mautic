<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Service\OptimisticLockServiceInterface;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

abstract class AbstractCampaignCommand extends MauticMysqlTestCase
{
    public const SEND_EMAIL_SECONDS = 3;

    public const CONDITION_SECONDS  = 6;

    public const DATE_TIME_ZONE = 'UTC';

    // Make sonar Qube happy
    public const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public const CAMPAIGN_NAME = 'Campaign Test';

    public const ADMIN_USER = 'Admin User';

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
     * @var \DateTimeInterface
     */
    protected $eventDate;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') || define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        // Everything needs to happen anonymously
        $this->defaultClientServer = $this->clientServer;
        $this->clientServer        = [];

        parent::setUp();

        $this->db     = $this->em->getConnection();
        $this->prefix = static::getContainer()->getParameter('mautic.db_table_prefix');

        // Populate contacts
        $this->installDatabaseFixtures([LeadFieldData::class, LoadLeadData::class]);

        date_default_timezone_set(self::DATE_TIME_ZONE);
        $this->eventDate = new \DateTime('now', new \DateTimeZone(self::DATE_TIME_ZONE));

        $sendEmailTimestamp = clone $this->eventDate;
        $sendEmailTimestamp->modify('+'.self::SEND_EMAIL_SECONDS.' seconds');

        $conditionTimestamp = clone $this->eventDate;
        $conditionTimestamp->modify('+'.self::CONDITION_SECONDS.' seconds');

        $this->insertLeadTags();
        $this->insertEmails();
        $this->insertCampaignWithEvents($sendEmailTimestamp, $conditionTimestamp);
        $this->insertLeadLists();
        $this->insertLeadListsLeads();
        $this->insertCampaignLeads();
    }

    public function beforeTearDown(): void
    {
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

    protected function getCampaignEventLogs(array $ids): array
    {
        $logs = $this->db->createQueryBuilder()
            ->select('l.email, l.country, event.name, event.event_type, event.type, log.*')
            ->from($this->prefix.'campaign_lead_event_log', 'log')
            ->join('log', $this->prefix.'campaign_events', 'event', 'event.id = log.event_id')
            ->join('log', $this->prefix.'leads', 'l', 'l.id = log.lead_id')
            ->where('log.campaign_id = 1')
            ->andWhere('log.event_id IN ('.implode(',', $ids).')')
            ->executeQuery()
            ->fetchAllAssociative();

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

    protected function createCampaignLead(Campaign $campaign, Lead $lead, bool $manuallyRemoved = false, int $rotation = 1): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $campaignLead->setManuallyRemoved($manuallyRemoved);
        $campaignLead->setRotation($rotation);
        $this->em->persist($campaignLead);

        return $campaignLead;
    }

    protected function createSegmentMember(LeadList $segment, Lead $lead): ListLead
    {
        $segmentMember = new ListLead();
        $segmentMember->setLead($lead);
        $segmentMember->setList($segment);
        $segmentMember->setDateAdded(new \DateTime());
        $this->em->persist($segmentMember);

        return $segmentMember;
    }

    protected function createEvent(string $name, Campaign $campaign, string $type, string $eventType, ?array $property = null): Event
    {
        $event = new Event(new \DateTime());
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

    protected function createEventLog(Lead $lead, Event $event, Campaign $campaign, int $rotation): LeadEventLog
    {
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $leadEventLog->setCampaign($campaign);
        $leadEventLog->setRotation($rotation);
        $leadEventLog->setDateTriggered(new \DateTime());
        $this->em->persist($leadEventLog);

        return $leadEventLog;
    }

    private function insertEmails(): void
    {
        $table        = $this->prefix.'emails';
        $commonFields = $this->loadJson('common_fields');

        $fieldTypes = [
            'date_added'            => Types::DATETIME_MUTABLE,
            'is_published'          => Types::BOOLEAN,
            // 'date_modified'         => Types::DATETIME_MUTABLE,
            // 'checked_out'           => Types::DATETIME_MUTABLE,
            // 'publish_up'            => Types::DATETIME_MUTABLE,
            // 'publish_down'          => Types::DATETIME_MUTABLE,
            // 'variant_start_date'    => Types::DATETIME_MUTABLE,
        ];

        $dateAdded1 = new \DateTime('2018-01-04 21:20:25', new \DateTimeZone(self::DATE_TIME_ZONE));
        $dateAdded2 = new \DateTime('2018-01-04 21:21:07', new \DateTimeZone(self::DATE_TIME_ZONE));

        $this->em->getConnection()->insert($table, array_merge($commonFields, [
            'id'          => 1,
            'date_added'  => $dateAdded1->format(self::DATE_TIME_FORMAT),
            'name'        => 'Campaign Test Email 1',
            'subject'     => 'Campaign Test Email 1',
            'custom_html' => $this->loadHtml('email1'),
        ]), $fieldTypes);

        $this->em->getConnection()->insert($table, array_merge($commonFields, [
            'id'          => 2,
            'date_added'  => $dateAdded2->format(self::DATE_TIME_FORMAT),
            'name'        => 'Campaign Test Email 2',
            'subject'     => 'Campaign Test Email 2',
            'custom_html' => $this->loadHtml('email2'),
        ]), $fieldTypes);

        DatabasePlatform::syncSerialSequence(
            $this->em->getConnection(),
            $table
        );
    }

    private function insertLeadTags(): void
    {
        $connection = $this->em->getConnection();
        $table      = $this->prefix.'lead_tags';

        $tags = $this->loadJson('lead_tags');

        foreach ($tags as $id => $tag) {
            $connection->insert($table, [
                'id'  => $id,
                'tag' => $tag,
            ]);
        }

        DatabasePlatform::syncSerialSequence(
            $this->em->getConnection(),
            $table
        );
    }

    private function insertCampaignWithEvents(\DateTime $sendEmailTimestamp, \DateTime $conditionTimestamp): void
    {
        $connection = $this->em->getConnection();
        $table1     = $this->prefix.'campaigns';

        $campaign = $this->loadJson('campaign');

        $connection->insert($table1, $campaign, [
            'allow_restart'       => Types::BOOLEAN,
            'is_published'        => Types::BOOLEAN,
            'date_added'          => Types::DATETIME_MUTABLE,
            'date_modified'       => Types::DATETIME_MUTABLE,
            // 'checked_out'         => Types::DATETIME_MUTABLE,
            // 'publish_up'          => Types::DATETIME_MUTABLE,
            // 'publish_down'        => Types::DATETIME_MUTABLE,
        ]);

        DatabasePlatform::syncSerialSequence(
            $this->em->getConnection(),
            $table1
        );

        $table2 = $this->prefix.'campaign_events';
        $events = $this->loadJson('campaign_events');

        foreach ($events as $event) {
            $fieldTypes = ['date_added' => Types::DATETIME_MUTABLE];

            switch ($event['id']) {
                case 2:
                    $event['trigger_date']      = $sendEmailTimestamp->format(self::DATE_TIME_FORMAT);
                    $fieldTypes['trigger_date'] = Types::DATETIME_MUTABLE;
                    break;
                case 4:
                case 5:
                    $event['trigger_date']      = $conditionTimestamp->format(self::DATE_TIME_FORMAT);
                    $fieldTypes['trigger_date'] = Types::DATETIME_MUTABLE;
                    break;
            }
            $connection->insert($table2, $event, $fieldTypes);
        }

        DatabasePlatform::syncSerialSequence(
            $this->em->getConnection(),
            $table2
        );
    }

    private function insertLeadLists(): void
    {
        $connection = $this->em->getConnection();
        $table      = $this->prefix.'lead_lists';

        $dateAdded = new \DateTime('2018-01-04 23:41:20', new \DateTimeZone(self::DATE_TIME_ZONE));

        $connection->insert($table, [
            'id'                   => 1,
            'is_preference_center' => false,
            'is_published'         => true,
            'date_added'           => $dateAdded,
            'created_by'           => 1,
            'created_by_user'      => self::ADMIN_USER,
            'date_modified'        => null,
            'modified_by'          => null,
            'modified_by_user'     => null,
            'checked_out'          => null,
            'checked_out_by'       => null,
            'checked_out_by_user'  => null,
            'name'                 => self::CAMPAIGN_NAME,
            'description'          => null,
            'alias'                => 'campaign-test',
            'filters'              => serialize([]), // a:0:{}
            'is_global'            => true,
            'public_name'          => 'campaign-test',
        ], [
            'is_preference_center' => Types::BOOLEAN,
            'is_published'         => Types::BOOLEAN,
            'date_added'           => Types::DATETIME_MUTABLE,
            // 'date_modified'        => Types::DATETIME_MUTABLE,
            // 'checked_out'          => Types::DATETIME_MUTABLE,
            'is_global'            => Types::BOOLEAN,
        ]);

        DatabasePlatform::syncSerialSequence(
            $this->em->getConnection(),
            $table
        );

        $connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id' => 1,
            'leadlist_id' => 1,
        ]);
    }

    private function insertLeadListsLeads(): void
    {
        $connection = $this->em->getConnection();
        $table      = $this->prefix.'lead_lists_leads';

        $dateAdded = new \DateTime('2018-01-04 22:47:00', new \DateTimeZone(self::DATE_TIME_ZONE));

        for ($leadId = 1; $leadId <= 50; ++$leadId) {
            $connection->insert($table, [
                'leadlist_id'      => 1,
                'lead_id'          => $leadId,
                'date_added'       => $dateAdded->format(self::DATE_TIME_FORMAT),
                'manually_removed' => false,
                'manually_added'   => true,
            ], [
                'date_added'       => Types::DATETIME_MUTABLE,
                'manually_removed' => Types::BOOLEAN,
                'manually_added'   => Types::BOOLEAN,
            ]);
        }
    }

    private function insertCampaignLeads(): void
    {
        $connection = $this->em->getConnection();
        $table      = $this->prefix.'campaign_leads';

        $dateAdded = new \DateTime('2018-01-04 22:47:30', new \DateTimeZone(self::DATE_TIME_ZONE));

        for ($leadId = 1; $leadId <= 50; ++$leadId) {
            $connection->insert($table, [
                'campaign_id'      => 1,
                'lead_id'          => $leadId,
                'date_added'       => $dateAdded->format(self::DATE_TIME_FORMAT),
                'manually_removed' => false,
                'manually_added'   => true,
                'date_last_exited' => null,
                'rotation'         => 1,
            ], [
                'date_added'        => Types::DATETIME_MUTABLE,
                'manually_removed'  => Types::BOOLEAN,
                'manually_added'    => Types::BOOLEAN,
                // 'date_last_exited'  => Types::DATETIME_MUTABLE,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function loadJson(string $name): array
    {
        $path = __DIR__.'/../Fixtures/'.$name.'.json';

        if (!file_exists($path)) {
            throw new FileNotFoundException(sprintf('Fixture file not found: %s', $path));
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new UnexpectedValueException(sprintf('Invalid JSON in fixture %s: %s', $path, json_last_error_msg()));
        }

        return $data;
    }

    private function loadHtml(string $name): string
    {
        $path = __DIR__.'/../Fixtures/'.$name.'.html';

        if (!file_exists($path)) {
            throw new FileNotFoundException(sprintf('HTML fixture not found: %s', $path));
        }

        return file_get_contents($path);
    }

    /**
     * Simulate a fully completed condition/decision event log by incrementing the version to 2.
     * A version=1 log means the event was inserted but execution never completed (stuck mid-execution).
     * A version=2 log means the event was fully executed and its children may now be considered.
     */
    protected function markEventLogAsCompleted(LeadEventLog $log): void
    {
        /** @var OptimisticLockServiceInterface $lockService */
        $lockService = self::getContainer()->get(OptimisticLockServiceInterface::class);
        $this->em->flush();
        $lockService->incrementVersion($log);
    }
}
