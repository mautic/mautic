<?php

namespace Mautic\CampaignBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;

class AbstractCampaignCommand extends MauticMysqlTestCase
{
    public const SEND_EMAIL_SECONDS = 3;

    public const CONDITION_SECONDS  = 6;

    public const DATE_TIME_ZONE = 'UTC';

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
        // Everything needs to happen anonymously
        $this->defaultClientServer = $this->clientServer;
        $this->clientServer        = [];

        parent::setUp();

        $this->db     = $this->em->getConnection();
        $this->prefix = static::getContainer()->getParameter('mautic.db_table_prefix');

        // Populate contacts
        $this->installDatabaseFixtures([LeadFieldData::class, LoadLeadData::class]);

        date_default_timezone_set(self::DATE_TIME_ZONE);
        $this->eventDate  = new \DateTime('now', new \DateTimeZone(self::DATE_TIME_ZONE));

        $sendEmailTimestamp = clone $this->eventDate;
        $sendEmailTimestamp->modify('+'.self::SEND_EMAIL_SECONDS.' seconds');

        $conditionTimestamp = clone $this->eventDate;
        $conditionTimestamp->modify('+'.self::CONDITION_SECONDS.' seconds');

        $this->insertLeadTags();
        $this->insertEmails();
        $this->insertCampaigns();
        $this->insertCampaignEvents($sendEmailTimestamp, $conditionTimestamp);
        $this->insertLeadLists();
        $this->insertCampaignLeadlistXref();
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

    protected function createCampaignLead(Campaign $campaign, Lead $lead, bool $manuallyRemoved = false): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $campaignLead->setManuallyRemoved($manuallyRemoved);
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

    private function insertEmails(): void
    {
        $table = $this->prefix.'emails';

        $utmTags = [
            'utmSource'   => null,
            'utmMedium'   => null,
            'utmCampaign' => null,
            'utmContent'  => null,
        ];

        $dynamicContent = [
            0 => [
                'tokenName' => 'Dynamic Content 1',
                'content'   => 'Default Dynamic Content',
                'filters'   => [
                    0 => [
                        'content' => null,
                        'filters' => [],
                    ],
                ],
            ],
        ];

        $commonFields = [
            'headers'               => '[]', // JSON empty array
            'category_id'           => null,
            'translation_parent_id' => null,
            'variant_parent_id'     => null,
            'unsubscribeform_id'    => null,
            'is_published'          => true,
            'created_by'            => 1,
            'created_by_user'       => 'Admin',
            'date_modified'         => null,
            'modified_by'           => null,
            'modified_by_user'      => null,
            'checked_out'           => null,
            'checked_out_by'        => null,
            'checked_out_by_user'   => null,
            'description'           => null,
            'from_address'          => null,
            'from_name'             => null,
            'reply_to_address'      => null,
            'bcc_address'           => null,
            'template'              => 'blank',
            'content'               => serialize([]),
            'utm_tags'              => serialize($utmTags),
            'plain_text'            => null,
            'email_type'            => 'template',
            'publish_up'            => null,
            'publish_down'          => null,
            'read_count'            => 0,
            'sent_count'            => 0,
            'revision'              => 1,
            'lang'                  => 'en',
            'variant_settings'      => serialize([]),
            'variant_start_date'    => null,
            'dynamic_content'       => serialize($dynamicContent),
            'variant_sent_count'    => 0,
            'variant_read_count'    => 0,
            'preference_center_id'  => null,
        ];

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

        // Email 1 - exact custom_html from SQL (complex builder output)
        $customHtml1 = <<<HTML
<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" style="" class=" js flexbox flexboxlegacy canvas canvastext webgl no-touch geolocation postmessage websqldatabase indexeddb hashchange history draganddrop websockets rgba hsla multiplebgs backgroundsize borderimage borderradius boxshadow textshadow opacity cssanimations csscolumns cssgradients cssreflections csstransforms csstransforms3d csstransitions fontface generatedcontent video audio localstorage sessionstorage webworkers applicationcache svg inlinesvg smil svgclippaths js csstransforms csstransforms3d csstransitions responsejs "><head>
        <title>{subject}</title>
        <meta name="viewport" content="width=device-width,initial-scale=1.0" />
        <style type="text/css" media="only screen and (max-width: 480px)">
            /* Mobile styles */
            @media only screen and (max-width: 480px) {
                [class="w320"] {
                    width: 320px !important;
                }
                [class="mobile-block"] {
                    width: 100% !important;
                    display: block !important;
                }
            }
        </style>
                                                                                                                   </head>
    <body style="margin:0" class="ui-sortable">
        <div data-section-wrapper="1">
            <center>
                <table data-section="1" style="width: 600;" width="600" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td>
                                <div data-slot-container="1" style="min-height: 30px" class="ui-sortable">
                                    <div data-slot="text">
                                        <br />
                                        <h2>Hello there!</h2>
                                        <br />
                                        We haven't heard from you for a while...
                                        <br />
                                        <br />
                                        {unsubscribe_text} | {webview_text}
                                        <br />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </center>
        </div>
</body></html>
HTML;

        $dateAdded2 = new \DateTime('2018-01-04 21:21:07', new \DateTimeZone(self::DATE_TIME_ZONE));

        // Email 2 - exact custom_html from SQL (simpler)
        $customHtml2 = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>{subject}</title>
        <meta name="viewport" content="width=device-width,initial-scale=1.0" />
        <style type="text/css" media="only screen and (max-width: 480px)">
            /* Mobile styles */
            @media only screen and (max-width: 480px) {

                [class="w320"] {
                    width: 320px !important;
                }

                [class="mobile-block"] {
                    width: 100% !important;
                    display: block !important;
                }
            }
        </style>
    </head>
    <body style="margin:0">
        <div data-section-wrapper="1">
            <center>
                <table data-section="1" style="width: 600;" width="600" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td>
                                <div data-slot-container="1" style="min-height: 30px">
                                    <div data-slot="text">
                                        <br />
                                        <h2>Hello there!</h2>
                                        <br />
                                        We haven't heard from you for a while...
                                        <br />
                                        <br />
                                        {unsubscribe_text} | {webview_text}
                                        <br />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </center>
        </div>
    </body>
</html>
HTML;

        $this->em->getConnection()->insert($table, array_merge($commonFields, [
            'id'          => 1,
            'date_added'  => $dateAdded1->format('Y-m-d H:i:s'),
            'name'        => 'Campaign Test Email 1',
            'subject'     => 'Campaign Test Email 1',
            'custom_html' => $customHtml1,
        ]), $fieldTypes);

        $this->em->getConnection()->insert($table, array_merge($commonFields, [
            'id'          => 2,
            'date_added'  => $dateAdded2->format('Y-m-d H:i:s'),
            'name'        => 'Campaign Test Email 2',
            'subject'     => 'Campaign Test Email 2',
            'custom_html' => $customHtml2,
        ]), $fieldTypes);
    }

    private function insertLeadTags(): void
    {
        $connection = $this->em->getConnection();
        $table      = $this->prefix.'lead_tags';

        $tags = [
            1  => 'CampaignTest',
            2  => 'US:NotOpen',
            3  => 'NonUS:NotOpen',
            4  => 'UK:NotOpen',
            5  => 'NonUK:NotOpen',
            6  => 'US:Action',
            7  => 'NonUS:Action',
            8  => 'Campaign Test',
            9  => 'EmailNotOpen',
            10 => 'ChainedAction',
        ];

        foreach ($tags as $id => $tag) {
            $connection->insert($table, [
                'id'  => $id,
                'tag' => $tag,
            ]);
        }
    }

    private function insertCampaigns(): void
    {
        $connection = $this->em->getConnection();
        $table      = $this->prefix.'campaigns';

        $dateAdded    = new \DateTime('2018-01-04 21:41:05', new \DateTimeZone(self::DATE_TIME_ZONE));
        $dateModified = new \DateTime('2018-03-08 23:27:28', new \DateTimeZone(self::DATE_TIME_ZONE));

        $canvasSettings = [
            'nodes' => [
                0  => ['id' => '1', 'positionX' => '577', 'positionY' => '155'],
                1  => ['id' => '2', 'positionX' => '842', 'positionY' => '164'],
                2  => ['id' => '3', 'positionX' => '842', 'positionY' => '269'],
                3  => ['id' => '11', 'positionX' => '389', 'positionY' => '252'],
                4  => ['id' => '4', 'positionX' => '1132', 'positionY' => '373'],
                5  => ['id' => '5', 'positionX' => '841', 'positionY' => '378'],
                6  => ['id' => '10', 'positionX' => '597', 'positionY' => '378'],
                7  => ['id' => '12', 'positionX' => '168', 'positionY' => '334'],
                8  => ['id' => '13', 'positionX' => '391', 'positionY' => '335'],
                9  => ['id' => '14', 'positionX' => '1372', 'positionY' => '364'],
                10 => ['id' => '6', 'positionX' => '649', 'positionY' => '496'],
                11 => ['id' => '7', 'positionX' => '874', 'positionY' => '488'],
                12 => ['id' => '8', 'positionX' => '1097', 'positionY' => '486'],
                13 => ['id' => '9', 'positionX' => '1313', 'positionY' => '491'],
                14 => ['id' => '15', 'positionX' => '1563', 'positionY' => '291'],
                15 => ['id' => 'lists', 'positionX' => '677', 'positionY' => '50'],
            ],
            'connections' => [
                0  => ['sourceId' => 'lists', 'targetId' => '1', 'anchors' => ['source' => 'leadsource', 'target' => 'top']],
                1  => ['sourceId' => 'lists', 'targetId' => '2', 'anchors' => ['source' => 'leadsource', 'target' => 'top']],
                2  => ['sourceId' => '2', 'targetId' => '3', 'anchors' => ['source' => 'bottom', 'target' => 'top']],
                3  => ['sourceId' => '3', 'targetId' => '4', 'anchors' => ['source' => 'no', 'target' => 'top']],
                4  => ['sourceId' => '3', 'targetId' => '5', 'anchors' => ['source' => 'no', 'target' => 'top']],
                5  => ['sourceId' => '5', 'targetId' => '6', 'anchors' => ['source' => 'yes', 'target' => 'top']],
                6  => ['sourceId' => '5', 'targetId' => '7', 'anchors' => ['source' => 'no', 'target' => 'top']],
                7  => ['sourceId' => '4', 'targetId' => '8', 'anchors' => ['source' => 'yes', 'target' => 'top']],
                8  => ['sourceId' => '4', 'targetId' => '9', 'anchors' => ['source' => 'no', 'target' => 'top']],
                9  => ['sourceId' => '3', 'targetId' => '10', 'anchors' => ['source' => 'yes', 'target' => 'top']],
                10 => ['sourceId' => '1', 'targetId' => '11', 'anchors' => ['source' => 'bottom', 'target' => 'top']],
                11 => ['sourceId' => '11', 'targetId' => '12', 'anchors' => ['source' => 'yes', 'target' => 'top']],
                12 => ['sourceId' => '11', 'targetId' => '13', 'anchors' => ['source' => 'no', 'target' => 'top']],
                13 => ['sourceId' => '3', 'targetId' => '14', 'anchors' => ['source' => 'no', 'target' => 'top']],
                14 => ['sourceId' => '3', 'targetId' => '15', 'anchors' => ['source' => 'no', 'target' => 'top']],
            ],
        ];

        $connection->insert($table, [
            'allow_restart'       => false,
            'id'                  => 1,
            'category_id'         => null,
            'is_published'        => true,
            'date_added'          => $dateAdded->format('Y-m-d H:i:s'),
            'created_by'          => 1,
            'created_by_user'     => 'Admin',
            'date_modified'       => $dateModified->format('Y-m-d H:i:s'),
            'modified_by'         => 1,
            'modified_by_user'    => 'Admin User',
            'checked_out'         => null,
            'checked_out_by'      => null,
            'checked_out_by_user' => 'Admin User',
            'name'                => 'Campaign Test',
            'description'         => null,
            'publish_up'          => null,
            'publish_down'        => null,
            'canvas_settings'     => serialize($canvasSettings),
        ], [
            'allow_restart'       => Types::BOOLEAN,
            'is_published'        => Types::BOOLEAN,
            'date_added'          => Types::DATETIME_MUTABLE,
            'date_modified'       => Types::DATETIME_MUTABLE,
            // 'checked_out'         => Types::DATETIME_MUTABLE,
            // 'publish_up'          => Types::DATETIME_MUTABLE,
            // 'publish_down'        => Types::DATETIME_MUTABLE,
        ]);
    }

    private function insertCampaignEvents(\DateTime $sendEmailTimestamp, \DateTime $conditionTimestamp): void
    {
        $connection = $this->em->getConnection();
        $table      = $this->prefix.'campaign_events';

        $fieldTypes = [
            'trigger_date'          => Types::DATETIME_MUTABLE,
        ];

        // Common buttons for form submission keys
        $buttons = ['save' => ''];

        // Common settings for tag change actions
        $tagSettings = [
            'label'       => "Modify contact's tags",
            'description' => 'Add tag to or remove tag from contact',
            'formType'    => 'modify_lead_tags',
            'eventName'   => 'mautic.lead.on_campaign_trigger_action',
        ];

        // Common settings for email send actions
        $emailSettings = [
            'label'           => 'Send email',
            'description'     => 'Send the selected email to the contact.',
            'eventName'       => 'mautic.email.on_campaign_trigger_action',
            'formType'        => 'emailsend_list',
            'formTypeOptions' => [
                'update_select'    => 'campaignevent_properties_email',
                'with_email_types' => true,
            ],
            'formTheme'      => 'MauticEmailBundle:FormTheme\EmailSendList',
            'channel'        => 'email',
            'channelIdField' => 'email',
        ];

        // Event 1: Tag CampaignTest (source action, adds tag ID 1)
        $properties1 = [
            'canvasSettings'      => ['droppedX' => '577', 'droppedY' => '155'],
            'name'                => '',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'leadsource',
            'properties'          => ['add_tags' => ['1']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'source',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['Campaign Test'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 1,
            'campaign_id'           => 1,
            'parent_id'             => null,
            'name'                  => 'Tag CampaignTest',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 1,
            'properties'            => serialize($properties1),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => null,
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 2: Send email 1
        $properties2 = [
            'canvasSettings'      => ['droppedX' => '337', 'droppedY' => '155'],
            'name'                => '',
            'triggerMode'         => 'date',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'leadsource',
            'properties'          => [
                'email'      => '1',
                'email_type' => 'transactional',
                'priority'   => '2',
                'attempts'   => '3',
            ],
            'type'            => 'email.send',
            'eventType'       => 'action',
            'anchorEventType' => 'source',
            'buttons'         => $buttons,
            'settings'        => $emailSettings,
            'email'           => '1',
            'email_type'      => 'transactional',
            'priority'        => 2,
            'attempts'        => 3,
        ];
        $connection->insert($table, [
            'id'                    => 2,
            'campaign_id'           => 1,
            'parent_id'             => null,
            'name'                  => 'Send email 1',
            'description'           => null,
            'type'                  => 'email.send',
            'event_type'            => 'action',
            'event_order'           => 1,
            'properties'            => serialize($properties2),
            'trigger_date'          => $sendEmailTimestamp->format('Y-m-d H:i:s'),
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'date',
            'decision_path'         => null,
            'temp_id'               => null,
            'channel'               => 'email',
            'channel_id'            => 1,
            'failed_count'          => 0,
        ], $fieldTypes);

        // Event 3: Opens email (decision - empty)
        $properties3 = [];
        $connection->insert($table, [
            'id'                    => 3,
            'campaign_id'           => 1,
            'parent_id'             => 2,
            'name'                  => 'Opens email',
            'description'           => null,
            'type'                  => 'email.open',
            'event_type'            => 'decision',
            'event_order'           => 2,
            'properties'            => serialize($properties3),
            'trigger_date'          => null,
            'trigger_interval'      => 0,
            'trigger_interval_unit' => null,
            'trigger_mode'          => null,
            'decision_path'         => null,
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 4: Is UK (condition - lead.field_value) - with duplicates
        $properties4 = [
            'canvasSettings'      => ['droppedX' => '942', 'droppedY' => '374'],
            'name'                => 'Is UK',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'no',
            'properties'          => [
                'field'    => 'country',
                'operator' => '=',
                'value'    => 'United Kingdom',
            ],
            'type'            => 'lead.field_value',
            'eventType'       => 'condition',
            'anchorEventType' => 'decision',
            'buttons'         => $buttons,
            'field'           => 'country',
            'operator'        => '=',
            'value'           => 'United Kingdom',
        ];
        $connection->insert($table, [
            'id'                    => 4,
            'campaign_id'           => 1,
            'parent_id'             => 3,
            'name'                  => 'Is UK',
            'description'           => null,
            'type'                  => 'lead.field_value',
            'event_type'            => 'condition',
            'event_order'           => 3,
            'properties'            => serialize($properties4),
            'trigger_date'          => $conditionTimestamp->format('Y-m-d H:i:s'),
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'date',
            'decision_path'         => 'no',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ], $fieldTypes);

        // Event 5: Is US (condition - duplicate structure)
        $properties5 = [
            'canvasSettings'      => ['droppedX' => '942', 'droppedY' => '374'],
            'name'                => 'Is US',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'no',
            'properties'          => [
                'field'    => 'country',
                'operator' => '=',
                'value'    => 'United States',
            ],
            'type'            => 'lead.field_value',
            'eventType'       => 'condition',
            'anchorEventType' => 'decision',
            'buttons'         => $buttons,
            'field'           => 'country',
            'operator'        => '=',
            'value'           => 'United States',
        ];

        $connection->insert($table, [
            'id'                    => 5,
            'campaign_id'           => 1,
            'parent_id'             => 3,
            'name'                  => 'Is US',
            'description'           => null,
            'type'                  => 'lead.field_value',
            'event_type'            => 'condition',
            'event_order'           => 3,
            'properties'            => serialize($properties5),
            'trigger_date'          => $conditionTimestamp->format('Y-m-d H:i:s'),
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'date',
            'decision_path'         => 'no',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ], $fieldTypes);

        // Event 6: Tag US:NotOpen
        $properties6 = [
            'canvasSettings'      => ['droppedX' => '741', 'droppedY' => '483'],
            'name'                => 'Tag US:NotOpen',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'yes',
            'properties'          => ['add_tags' => ['2']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'condition',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['US:NotOpen'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 6,
            'campaign_id'           => 1,
            'parent_id'             => 5,
            'name'                  => 'Tag US:NotOpen',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 4,
            'properties'            => serialize($properties6),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => 'yes',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 7: Tag NonUS:NotOpen
        $properties7 = [
            'canvasSettings'      => ['droppedX' => '981', 'droppedY' => '483'],
            'name'                => 'Tag NonUS:NotOpen',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'no',
            'properties'          => ['add_tags' => ['3']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'condition',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['NonUS:NotOpen'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 7,
            'campaign_id'           => 1,
            'parent_id'             => 5,
            'name'                  => 'Tag NonUS:NotOpen',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 4,
            'properties'            => serialize($properties7),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => 'no',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 8: Tag UK:NotOpen
        $properties8 = [
            'canvasSettings'      => ['droppedX' => '501', 'droppedY' => '480'],
            'name'                => 'Tag UK:NotOpen',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'yes',
            'properties'          => ['add_tags' => ['4']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'condition',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['UK:NotOpen'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 8,
            'campaign_id'           => 1,
            'parent_id'             => 4,
            'name'                  => 'Tag UK:NotOpen',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 4,
            'properties'            => serialize($properties8),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => 'yes',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 9: Tag NonUK:NotOpen
        $properties9 = [
            'canvasSettings'      => ['droppedX' => '1221', 'droppedY' => '480'],
            'name'                => 'Tag NonUK:NotOpen',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'no',
            'properties'          => ['add_tags' => ['5']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'condition',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['NonUK:NotOpen'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 9,
            'campaign_id'           => 1,
            'parent_id'             => 4,
            'name'                  => 'Tag NonUK:NotOpen',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 4,
            'properties'            => serialize($properties9),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => 'no',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 10: Send email 2
        $properties10 = [
            'canvasSettings'      => ['droppedX' => '742', 'droppedY' => '374'],
            'name'                => 'Send email 2',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'yes',
            'properties'          => [
                'email'      => '2',
                'email_type' => 'transactional',
                'priority'   => '2',
                'attempts'   => '3',
            ],
            'type'            => 'email.send',
            'eventType'       => 'action',
            'anchorEventType' => 'decision',
            'buttons'         => $buttons,
            'settings'        => $emailSettings,
            'email'           => '2',
            'email_type'      => 'transactional',
            'priority'        => 2,
            'attempts'        => 3.0,
        ];
        $connection->insert($table, [
            'id'                    => 10,
            'campaign_id'           => 1,
            'parent_id'             => 3,
            'name'                  => 'Send email 2',
            'description'           => null,
            'type'                  => 'email.send',
            'event_type'            => 'action',
            'event_order'           => 3,
            'properties'            => serialize($properties10),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => 'yes',
            'temp_id'               => null,
            'channel'               => 'email',
            'channel_id'            => 2,
            'failed_count'          => 0,
        ]);

        // Event 11: Is US (condition off first tag action)
        $properties11 = [
            'canvasSettings'      => ['droppedX' => '577', 'droppedY' => '260'],
            'name'                => 'Is US',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'bottom',
            'properties'          => [
                'field'    => 'country',
                'operator' => '=',
                'value'    => 'United States',
            ],
            'type'            => 'lead.field_value',
            'eventType'       => 'condition',
            'anchorEventType' => 'action',
            'buttons'         => $buttons,
            'field'           => 'country',
            'operator'        => '=',
            'value'           => 'United States',
        ];
        $connection->insert($table, [
            'id'                    => 11,
            'campaign_id'           => 1,
            'parent_id'             => 1,
            'name'                  => 'Is US',
            'description'           => null,
            'type'                  => 'lead.field_value',
            'event_type'            => 'condition',
            'event_order'           => 2,
            'properties'            => serialize($properties11),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => null,
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 12: Tag US:Action
        $properties12 = [
            'canvasSettings'      => ['droppedX' => '12', 'droppedY' => '357'],
            'name'                => 'Tag US:Action',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'yes',
            'properties'          => ['add_tags' => ['6']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'condition',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['US:Action'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 12,
            'campaign_id'           => 1,
            'parent_id'             => 11,
            'name'                  => 'Tag US:Action',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 3,
            'properties'            => serialize($properties12),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => 'yes',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 13: Tag NonUS:Action
        $properties13 = [
            'canvasSettings'      => ['droppedX' => '489', 'droppedY' => '357'],
            'name'                => 'Tag NonUS:Action',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'no',
            'properties'          => ['add_tags' => ['7']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'condition',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['NonUS:Action'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 13,
            'campaign_id'           => 1,
            'parent_id'             => 11,
            'name'                  => 'Tag NonUS:Action',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 3,
            'properties'            => serialize($properties13),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => 'no',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 14: Tag EmailNotOpen (interval trigger)
        $properties14 = [
            'canvasSettings'      => ['droppedX' => '1081', 'droppedY' => '374'],
            'name'                => 'Tag EmailNotOpen',
            'triggerMode'         => 'interval',
            'triggerDate'         => null,
            'triggerInterval'     => '2',
            'triggerIntervalUnit' => 'i',
            'anchor'              => 'no',
            'properties'          => ['add_tags' => ['9']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'decision',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['EmailNotOpen'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 14,
            'campaign_id'           => 1,
            'parent_id'             => 3,
            'name'                  => 'Tag EmailNotOpen',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 3,
            'properties'            => serialize($properties14),
            'trigger_date'          => null,
            'trigger_interval'      => 2,
            'trigger_interval_unit' => 'i',
            'trigger_mode'          => 'interval',
            'decision_path'         => 'no',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 15: Tag EmailNotOpen Again (interval trigger)
        $properties15 = [
            'canvasSettings'      => ['droppedX' => '1612', 'droppedY' => '374'],
            'name'                => 'Tag EmailNotOpen Again',
            'triggerMode'         => 'interval',
            'triggerDate'         => null,
            'triggerInterval'     => '6',
            'triggerIntervalUnit' => 'i',
            'anchor'              => 'no',
            'properties'          => ['add_tags' => ['9']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'decision',
            'buttons'             => $buttons,
            'add_tags'            => ['EmailNotOpen'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 15,
            'campaign_id'           => 1,
            'parent_id'             => 3,
            'name'                  => 'Tag EmailNotOpen Again',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 3,
            'properties'            => serialize($properties15),
            'trigger_date'          => null,
            'trigger_interval'      => 6,
            'trigger_interval_unit' => 'i',
            'trigger_mode'          => 'interval',
            'decision_path'         => 'no',
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);

        // Event 16: Chained Action (adds tag ID 10)
        // Event 16: Tag ChainedAction (chained off event 12)
        $properties16 = [
            'canvasSettings'      => ['droppedX' => '168', 'droppedY' => '439'],
            'name'                => 'Chained Action',
            'triggerMode'         => 'immediate',
            'triggerDate'         => null,
            'triggerInterval'     => '1',
            'triggerIntervalUnit' => 'd',
            'anchor'              => 'bottom',
            'properties'          => ['add_tags' => ['10']],
            'type'                => 'lead.changetags',
            'eventType'           => 'action',
            'anchorEventType'     => 'action',
            'buttons'             => $buttons,
            'settings'            => $tagSettings,
            'add_tags'            => ['ChainedAction'],
            'remove_tags'         => [],
        ];
        $connection->insert($table, [
            'id'                    => 16,
            'campaign_id'           => 1,
            'parent_id'             => 12,
            'name'                  => 'Chained Action',
            'description'           => null,
            'type'                  => 'lead.changetags',
            'event_type'            => 'action',
            'event_order'           => 4,
            'properties'            => serialize($properties16),
            'trigger_date'          => null,
            'trigger_interval'      => 1,
            'trigger_interval_unit' => 'd',
            'trigger_mode'          => 'immediate',
            'decision_path'         => null,
            'temp_id'               => null,
            'channel'               => null,
            'channel_id'            => null,
            'failed_count'          => 0,
        ]);
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
            'created_by_user'      => 'Admin User',
            'date_modified'        => null,
            'modified_by'          => null,
            'modified_by_user'     => null,
            'checked_out'          => null,
            'checked_out_by'       => null,
            'checked_out_by_user'  => null,
            'name'                 => 'Campaign Test',
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
    }

    private function insertCampaignLeadlistXref(): void
    {
        $connection = $this->em->getConnection();
        $table      = $this->prefix.'campaign_leadlist_xref';

        $connection->insert($table, [
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
                'date_added'       => $dateAdded->format('Y-m-d H:i:s'),
                'manually_removed' => false,
                'manually_added'   => true,
            ], [
                'date_added'        => Types::DATETIME_MUTABLE,
                'manually_removed'  => Types::BOOLEAN,
                'manually_added'    => Types::BOOLEAN,
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
                'date_added'       => $dateAdded->format('Y-m-d H:i:s'),
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
}
