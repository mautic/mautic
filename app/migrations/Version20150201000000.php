<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Schema update for Version 1.0.0-beta3 to 1.0.0-beta4
 *
 * Class Version20150201000000
 *
 * @package Mautic\Migrations
 */
class Version20150201000000 extends AbstractMauticMigration
{
    protected $campaigns;

    public function preUp(Schema $schema)
    {
        // Test to see if this migration has already been applied
        $campaignTable = $schema->getTable($this->prefix . 'campaigns');
        if ($campaignTable->hasColumn('canvas_settings')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->campaigns      = $this->connection->createQueryBuilder()->select('c.id')->from($this->prefix . 'campaigns', 'c')->execute()->fetchAll();
        $this->campaignEvents = $this->connection->createQueryBuilder()->select('e.id, e.campaign_id, e.temp_id, e.parent_id, e.decision_path, e.canvas_settings')->from($this->prefix . 'campaign_events', 'e')->execute()->fetchAll();
    }

    public function postUp(Schema $schema)
    {
        // Build the new format for campaigns and events
        $em = $this->factory->getEntityManager();

        $campaignEntities = $eventEntities = $events = $tempIds = array();

        foreach ($this->campaignEvents as $e) {
            $campaignId = $e['campaign_id'];

            if (!isset($events[$campaignId])) {
                $events[$campaignId] = array();
            }

            // Collect event canvas settings and get temp Ids
            $tempIds[$e['temp_id']] = $e['id'];

            $canvasSettings = unserialize($e['canvas_settings']);

            $sourceEp = '';
            if (!empty($e['decision_path'])) {
                $sourceEp = $e['decision_path'];
            } elseif (!empty($e['parent_id'])) {
                $sourceEp = 'bottom';
            }

            $events[$campaignId][$e['id']] = array(
                'positionY' => $canvasSettings['droppedY'],
                'positionX' => $canvasSettings['droppedX'],
                'tempId'    => $e['temp_id'],
                'sourceId'  => $e['parent_id'],
                'sourceEp'  => $sourceEp,
                'targetId'  => $e['id'],
                'targetEp'  => (!empty($e['parent_id'])) ? 'top' : ''
            );
        }

        foreach ($this->campaigns as $campaign) {
            $campaignId = $campaign['id'];

            $settings = array('nodes' => array(), 'connections' => array());
            $data     = (isset($events[$campaignId])) ? $events[$campaignId] : array();

            foreach ($data as $id => $details) {
                // Node placement
                $settings['nodes'][] = array(
                    'id'        => $id,
                    'positionX' => $details['positionX'],
                    'positionY' => $details['positionY']
                );

                $settings['connections'][] = array(
                    'sourceId' => (strpos($details['sourceId'], 'new') !== false) ? str_replace($details['sourceId'], $tempIds[$details['sourceId']], $details['sourceId']) : $details['sourceId'],
                    'targetId' => (strpos($details['targetId'], 'new') !== false) ? str_replace($details['targetId'], $tempIds[$details['targetId']], $details['targetId']) : $details['targetId'],
                    'anchors'  => array(
                        'source' => $details['sourceEp'],
                        'target' => $details['targetEp']
                    )
                );
            }

            $entity = $em->getReference('MauticCampaignBundle:Campaign', $campaignId);
            $entity->setCanvasSettings($settings);

            $campaignEntities[] = $entity;
        }

        if (!empty($campaignEntities)) {
            $repo = $this->factory->getModel('campaign')->getRepository();
            $repo->saveEntities($campaignEntities);
        }

        // Update default reports
        $reportsModel = $this->factory->getModel('report');
        $reportsRepo  = $reportsModel->getRepository();
        $reports      = $reportsModel->getEntities(array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => 'r.id',
                        'expr'   => 'lte',
                        'value'  => 5
                    )
                )
            )
        ));

        /** @var \Mautic\ReportBundle\Entity\Report $report */
        foreach ($reports as $report) {
            switch ($report->getId()) {
                case 1:
                    $source  = 'page.hits';
                    $columns = 'a:7:{i:0;s:11:"ph.date_hit";i:1;s:6:"ph.url";i:2;s:12:"ph.url_title";i:3;s:10:"ph.referer";i:4;s:12:"i.ip_address";i:5;s:7:"ph.city";i:6;s:10:"ph.country";}';
                    $filters = 'a:2:{i:0;a:3:{s:6:"column";s:7:"ph.code";s:9:"condition";s:2:"eq";s:5:"value";s:3:"200";}i:1;a:3:{s:6:"column";s:14:"p.is_published";s:9:"condition";s:2:"eq";s:5:"value";s:1:"1";}}';
                    $order   = 'a:1:{i:0;a:2:{s:6:"column";s:11:"ph.date_hit";s:9:"direction";s:3:"ASC";}}';
                    $graphs  = 'a:8:{i:0;s:35:"mautic.page.graph.line.time.on.site";i:1;s:27:"mautic.page.graph.line.hits";i:2;s:38:"mautic.page.graph.pie.new.vs.returning";i:3;s:31:"mautic.page.graph.pie.languages";i:4;s:34:"mautic.page.graph.pie.time.on.site";i:5;s:27:"mautic.page.table.referrers";i:6;s:30:"mautic.page.table.most.visited";i:7;s:37:"mautic.page.table.most.visited.unique";}';
                    break;

                case 2:
                    $source = 'asset.downloads';
                    $columns = 'a:7:{i:0;s:16:"ad.date_download";i:1;s:7:"a.title";i:2;s:12:"i.ip_address";i:3;s:11:"l.firstname";i:4;s:10:"l.lastname";i:5;s:7:"l.email";i:6;s:4:"a.id";}';
                    $filters = 'a:1:{i:0;a:3:{s:6:"column";s:14:"a.is_published";s:9:"condition";s:2:"eq";s:5:"value";s:1:"1";}}';
                    $order  = 'a:1:{i:0;a:2:{s:6:"column";s:16:"ad.date_download";s:9:"direction";s:3:"ASC";}}';
                    $graphs = 'a:4:{i:0;s:33:"mautic.asset.graph.line.downloads";i:1;s:31:"mautic.asset.graph.pie.statuses";i:2;s:34:"mautic.asset.table.most.downloaded";i:3;s:32:"mautic.asset.table.top.referrers";}';
                    break;

                case 3:
                    $source = 'form.submissions';
                    $columns = 'a:0:{}';
                    $filters = 'a:1:{i:1;a:3:{s:6:"column";s:14:"f.is_published";s:9:"condition";s:2:"eq";s:5:"value";s:1:"1";}}';
                    $order = 'a:0:{}';
                    $graphs = 'a:3:{i:0;s:34:"mautic.form.graph.line.submissions";i:1;s:32:"mautic.form.table.most.submitted";i:2;s:31:"mautic.form.table.top.referrers";}';
                    break;

                case 4:
                    $source = 'email.stats';
                    $columns = 'a:5:{i:0;s:12:"es.date_sent";i:1;s:12:"es.date_read";i:2;s:9:"e.subject";i:3;s:16:"es.email_address";i:4;s:4:"e.id";}';
                    $filters = 'a:1:{i:0;a:3:{s:6:"column";s:14:"e.is_published";s:9:"condition";s:2:"eq";s:5:"value";s:1:"1";}}';
                    $order = 'a:1:{i:0;a:2:{s:6:"column";s:12:"es.date_sent";s:9:"direction";s:3:"ASC";}}';
                    $graphs = 'a:6:{i:0;s:29:"mautic.email.graph.line.stats";i:1;s:42:"mautic.email.graph.pie.ignored.read.failed";i:2;s:35:"mautic.email.table.most.emails.read";i:3;s:35:"mautic.email.table.most.emails.sent";i:4;s:43:"mautic.email.table.most.emails.read.percent";i:5;s:37:"mautic.email.table.most.emails.failed";}';
                    break;

                case 5:
                    $source = 'lead.pointlog';
                    $columns = 'a:7:{i:0;s:13:"lp.date_added";i:1;s:7:"lp.type";i:2;s:13:"lp.event_name";i:3;s:11:"l.firstname";i:4;s:10:"l.lastname";i:5;s:7:"l.email";i:6;s:8:"lp.delta";}';
                    $filters = 'a:0:{}';
                    $order = 'a:1:{i:0;a:2:{s:6:"column";s:13:"lp.date_added";s:9:"direction";s:3:"ASC";}}';
                    $graphs = 'a:6:{i:0;s:29:"mautic.lead.graph.line.points";i:1;s:29:"mautic.lead.table.most.points";i:2;s:29:"mautic.lead.table.top.actions";i:3;s:28:"mautic.lead.table.top.cities";i:4;s:31:"mautic.lead.table.top.countries";i:5;s:28:"mautic.lead.table.top.events";}';
                    break;
            }

            $report->setSource($source);
            $report->setColumns(unserialize($columns));
            $report->setFilters(unserialize($filters));
            $report->setTableOrder(unserialize($order));
            $report->setGraphs(unserialize($graphs));

            $reportsRepo->saveEntity($report);
        }
    }

    public function mysqlUp(Schema $schema) {
        if (!$this->applicable) {
            return;
        }

        $this->addSql('ALTER TABLE ' . $this->prefix.'oauth2_accesstokens ADD token VARCHAR(255) NOT NULL, ADD expires_at INT DEFAULT NULL, ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX ' . $this->generatePropertyName('oauth2_accesstokens', 'uniq', array('token')) . ' ON ' . $this->prefix.'oauth2_accesstokens (token)');
        $this->addSql('ALTER TABLE ' . $this->prefix.'oauth2_authcodes ADD token VARCHAR(255) NOT NULL, ADD expires_at INT DEFAULT NULL, ADD scope VARCHAR(255) DEFAULT NULL, ADD redirect_uri LONGTEXT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX ' . $this->generatePropertyName('oauth2_authcodes', 'uniq', array('token')) . ' ON ' . $this->prefix.'oauth2_authcodes (token)');
        $this->addSql('ALTER TABLE ' . $this->prefix.'oauth2_clients ADD random_id VARCHAR(255) NOT NULL, ADD secret VARCHAR(255) NOT NULL, ADD redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ADD allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE ' . $this->prefix.'oauth2_refreshtokens ADD token VARCHAR(255) NOT NULL, ADD expires_at INT DEFAULT NULL, ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX ' . $this->generatePropertyName('oauth2_refreshtokens', 'uniq', array('token')) . ' ON ' . $this->prefix.'oauth2_refreshtokens (token)');

        $this->addSql('ALTER TABLE ' . $this->prefix.'assets DROP FOREIGN KEY ' . $this->findPropertyName('assets', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'assets DROP FOREIGN KEY ' . $this->findPropertyName('assets', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'assets DROP FOREIGN KEY ' . $this->findPropertyName('assets', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('assets', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'assets');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('assets', 'idx', '25F94802') . ' ON ' . $this->prefix.'assets');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('assets', 'idx', '87C0719F') . ' ON ' . $this->prefix.'assets');

        $this->addSql('ALTER TABLE ' . $this->prefix.'campaigns DROP FOREIGN KEY ' . $this->findPropertyName('campaigns', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'campaigns DROP FOREIGN KEY ' . $this->findPropertyName('campaigns', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'campaigns DROP FOREIGN KEY ' . $this->findPropertyName('campaigns', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('campaigns', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'campaigns');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('campaigns', 'idx', '25F94802') . ' ON ' . $this->prefix.'campaigns');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('campaigns', 'idx', '87C0719F') . ' ON ' . $this->prefix.'campaigns');
        $this->addSql('ALTER TABLE ' . $this->prefix.'campaigns ADD canvas_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');

        $this->addSql('ALTER TABLE ' . $this->prefix.'campaign_events DROP FOREIGN KEY ' . $this->findPropertyName('campaign_events', 'fk', '727ACA70'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'campaign_events DROP canvas_settings');
        $this->addSql('ALTER TABLE ' . $this->prefix.'campaign_events ADD CONSTRAINT ' . $this->findPropertyName('campaign_events', 'fk', '727ACA70') . ' FOREIGN KEY (parent_id) REFERENCES ' . $this->prefix.'campaign_events (id)');

        $this->addSql('ALTER TABLE ' . $this->prefix.'categories DROP FOREIGN KEY ' . $this->findPropertyName('categories', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'categories DROP FOREIGN KEY ' . $this->findPropertyName('categories', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'categories DROP FOREIGN KEY ' . $this->findPropertyName('categories', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('categories', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'categories');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('categories', 'idx', '25F94802') . ' ON ' . $this->prefix.'categories');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('categories', 'idx', '87C0719F') . ' ON ' . $this->prefix.'categories');

        $this->addSql('ALTER TABLE ' . $this->prefix.'email_donotemail DROP FOREIGN KEY ' . $this->findPropertyName('email_donotemail', 'fk', '55458D'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'email_donotemail DROP FOREIGN KEY ' . $this->findPropertyName('email_donotemail', 'fk', 'A832C1C9'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'email_donotemail ADD CONSTRAINT ' . $this->findPropertyName('email_donotemail', 'fk', '55458D') . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix.'leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . $this->prefix.'email_donotemail ADD CONSTRAINT ' . $this->findPropertyName('email_donotemail', 'fk', 'A832C1C9') . ' FOREIGN KEY (email_id) REFERENCES ' . $this->prefix.'emails (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix.'emails DROP FOREIGN KEY ' . $this->findPropertyName('emails', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'emails DROP FOREIGN KEY ' . $this->findPropertyName('emails', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'emails DROP FOREIGN KEY ' . $this->findPropertyName('emails', 'fk', 'DE12AB56'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'emails DROP FOREIGN KEY ' . $this->findPropertyName('emails', 'fk', '91861123'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('emails', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'emails');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('emails', 'idx', '25F94802') . ' ON ' . $this->prefix.'emails');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('emails', 'idx', '87C0719F') . ' ON ' . $this->prefix.'emails');
        $this->addSql('ALTER TABLE ' . $this->prefix.'emails ADD CONSTRAINT ' . $this->findPropertyName('assets', 'fk', '91861123') . ' FOREIGN KEY (variant_parent_id) REFERENCES ' . $this->prefix.'emails (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix.'email_stats ADD tokens LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');

        $this->addSql('ALTER TABLE ' . $this->prefix.'forms DROP FOREIGN KEY ' . $this->findPropertyName('forms', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'forms DROP FOREIGN KEY ' . $this->findPropertyName('forms', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'forms DROP FOREIGN KEY ' . $this->findPropertyName('forms', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('forms', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'forms');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('forms', 'idx', '25F94802') . ' ON ' . $this->prefix.'forms');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('forms', 'idx', '87C0719F') . ' ON ' . $this->prefix.'forms');
        $this->addSql('ALTER TABLE ' . $this->prefix.'forms ADD template VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix.'leads DROP FOREIGN KEY ' . $this->findPropertyName('leads', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'leads DROP FOREIGN KEY ' . $this->findPropertyName('leads', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'leads DROP FOREIGN KEY ' . $this->findPropertyName('leads', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('leads', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'leads');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('leads', 'idx', '25F94802') . ' ON ' . $this->prefix.'leads');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('leads', 'idx', '87C0719F') . ' ON ' . $this->prefix.'leads');

        $this->addSql('ALTER TABLE ' . $this->prefix.'leads ADD last_active DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_fields DROP FOREIGN KEY ' . $this->findPropertyName('lead_fields', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_fields DROP FOREIGN KEY ' . $this->findPropertyName('lead_fields', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_fields DROP FOREIGN KEY ' . $this->findPropertyName('lead_fields', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_fields', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'lead_fields');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_fields', 'idx', '25F94802') . ' ON ' . $this->prefix.'lead_fields');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_fields', 'idx', '87C0719F') . ' ON ' . $this->prefix.'lead_fields');

        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_lists DROP FOREIGN KEY ' . $this->findPropertyName('lead_lists', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_lists DROP FOREIGN KEY ' . $this->findPropertyName('lead_lists', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_lists DROP FOREIGN KEY ' . $this->findPropertyName('lead_lists', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_lists', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'lead_lists');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_lists', 'idx', '25F94802') . ' ON ' . $this->prefix.'lead_lists');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_lists', 'idx', '87C0719F') . ' ON ' . $this->prefix.'lead_lists');

        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_notes DROP FOREIGN KEY ' . $this->findPropertyName('lead_notes', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_notes DROP FOREIGN KEY ' . $this->findPropertyName('lead_notes', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'lead_notes DROP FOREIGN KEY ' . $this->findPropertyName('lead_notes', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_notes', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'lead_notes');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_notes', 'idx', '25F94802') . ' ON ' . $this->prefix.'lead_notes');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('lead_notes', 'idx', '87C0719F') . ' ON ' . $this->prefix.'lead_notes');

        $this->addSql('ALTER TABLE ' . $this->prefix.'page_hits ADD url_title VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix.'pages DROP FOREIGN KEY ' . $this->findPropertyName('pages', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'pages DROP FOREIGN KEY ' . $this->findPropertyName('pages', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'pages DROP FOREIGN KEY ' . $this->findPropertyName('pages', 'fk', 'DE12AB56'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'pages DROP FOREIGN KEY ' . $this->findPropertyName('pages', 'fk', '9091A2FB'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('pages', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'pages');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('pages', 'idx', '25F94802') . ' ON ' . $this->prefix.'pages');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('pages', 'idx', '87C0719F') . ' ON ' . $this->prefix.'pages');
        $this->addSql('ALTER TABLE ' . $this->prefix.'pages ADD CONSTRAINT ' . $this->findPropertyName('assets', 'fk', '9091A2FB') . ' FOREIGN KEY (translation_parent_id) REFERENCES ' . $this->prefix.'pages (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix.'page_redirects DROP FOREIGN KEY ' . $this->findPropertyName('page_redirects', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'page_redirects DROP FOREIGN KEY ' . $this->findPropertyName('page_redirects', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'page_redirects DROP FOREIGN KEY ' . $this->findPropertyName('page_redirects', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('page_redirects', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'page_redirects');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('page_redirects', 'idx', '25F94802') . ' ON ' . $this->prefix.'page_redirects');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('page_redirects', 'idx', '87C0719F') . ' ON ' . $this->prefix.'page_redirects');

        $this->addSql('ALTER TABLE ' . $this->prefix.'points DROP FOREIGN KEY ' . $this->findPropertyName('points', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'points DROP FOREIGN KEY ' . $this->findPropertyName('points', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'points DROP FOREIGN KEY ' . $this->findPropertyName('points', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('points', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'points');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('points', 'idx', '25F94802') . ' ON ' . $this->prefix.'points');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('points', 'idx', '87C0719F') . ' ON ' . $this->prefix.'points');

        $this->addSql('ALTER TABLE ' . $this->prefix.'point_triggers DROP FOREIGN KEY ' . $this->findPropertyName('point_triggers', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'point_triggers DROP FOREIGN KEY ' . $this->findPropertyName('point_triggers', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'point_triggers DROP FOREIGN KEY ' . $this->findPropertyName('point_triggers', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('point_triggers', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'point_triggers');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('point_triggers', 'idx', '25F94802') . ' ON ' . $this->prefix.'point_triggers');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('point_triggers', 'idx', '87C0719F') . ' ON ' . $this->prefix.'point_triggers');

        $this->addSql('ALTER TABLE ' . $this->prefix.'reports DROP FOREIGN KEY ' . $this->findPropertyName('reports', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'reports DROP FOREIGN KEY ' . $this->findPropertyName('reports', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'reports DROP FOREIGN KEY ' . $this->findPropertyName('reports', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('reports', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'reports');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('reports', 'idx', '25F94802') . ' ON ' . $this->prefix.'reports');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('reports', 'idx', '87C0719F') . ' ON ' . $this->prefix.'reports');
        $this->addSql('ALTER TABLE ' . $this->prefix.'reports ADD table_order LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', ADD graphs LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE columns columns LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE filters filters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE title name VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix.'roles DROP FOREIGN KEY ' . $this->findPropertyName('roles', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'roles DROP FOREIGN KEY ' . $this->findPropertyName('roles', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'roles DROP FOREIGN KEY ' . $this->findPropertyName('roles', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX  ' . $this->findPropertyName('roles', 'idx', 'DE12AB56') .' ON ' . $this->prefix.'roles');
        $this->addSql('DROP INDEX  ' . $this->findPropertyName('roles', 'idx', '25F94802') .' ON ' . $this->prefix.'roles');
        $this->addSql('DROP INDEX  ' . $this->findPropertyName('roles', 'idx', '87C0719F') .' ON ' . $this->prefix.'roles');

        $this->addSql('ALTER TABLE ' . $this->prefix.'users DROP FOREIGN KEY ' . $this->findPropertyName('users', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'users DROP FOREIGN KEY ' . $this->findPropertyName('users', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'users DROP FOREIGN KEY ' . $this->findPropertyName('users', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('users', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'users');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('users', 'idx', '25F94802') . ' ON ' . $this->prefix.'users');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('users', 'idx', '87C0719F') . ' ON ' . $this->prefix.'users');

        $this->addSql('ALTER TABLE ' . $this->prefix.'chat_channels DROP FOREIGN KEY ' . $this->findPropertyName('chat_channels', 'fk', '25F94802'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'chat_channels DROP FOREIGN KEY ' . $this->findPropertyName('chat_channels', 'fk', '87C0719F'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'chat_channels DROP FOREIGN KEY ' . $this->findPropertyName('chat_channels', 'fk', 'DE12AB56'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('chat_channels', 'idx', 'DE12AB56') . ' ON ' . $this->prefix.'chat_channels');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('chat_channels', 'idx', '25F94802') . ' ON ' . $this->prefix.'chat_channels');
        $this->addSql('DROP INDEX ' . $this->findPropertyName('chat_channels', 'idx', '87C0719F') . ' ON ' . $this->prefix.'chat_channels');

        $this->addSql('ALTER TABLE ' . $this->prefix.'chats DROP FOREIGN KEY ' . $this->findPropertyName('chats', 'fk', '6A7DC786'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'chats DROP FOREIGN KEY ' . $this->findPropertyName('chats', 'fk', 'F8050BAA'));
        $this->addSql('ALTER TABLE ' . $this->prefix.'chats ADD CONSTRAINT ' . $this->findPropertyName('chats', 'fk', '6A7DC786') . ' FOREIGN KEY (to_user) REFERENCES ' . $this->prefix.'users (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix.'chats ADD CONSTRAINT ' . $this->findPropertyName('chats', 'fk', 'F8050BAA') . ' FOREIGN KEY (from_user) REFERENCES ' . $this->prefix.'users (id)');

        $this->addSql('ALTER TABLE ' . $this->prefix.'assets ADD storage_location VARCHAR(255) NOT NULL, ADD remote_path VARCHAR(255) DEFAULT NULL');
    }

    /**
     * Due to SQL errors, these could not be installed with beta3
     */
    public function postgresqlUp(Schema $schema) {}
    public function mssqlUp(Schema $schema) {}
}
