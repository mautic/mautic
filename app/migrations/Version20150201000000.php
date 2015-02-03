<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Schema update for Version 1.0.0-beta3 to 1.0.0-beta4
 *
 * Class Version1_0_0beta4
 *
 * @package Mautic\Migrations
 */
class Version20150201000000 extends AbstractMauticMigration
{
    protected $campaigns;

    public function preUp(Schema $schema)
    {
        $connection           = $this->factory->getEntityManager()->getConnection();
        $this->campaigns      = $connection->createQueryBuilder()->select('c.id')->from($this->prefix . 'campaigns', 'c')->execute()->fetchAll();
        $this->campaignEvents = $connection->createQueryBuilder()->select('e.id, e.campaign_id, e.temp_id, e.parent_id, e.decision_path, e.canvas_settings')->from($this->prefix . 'campaign_events', 'e')->execute()->fetchAll();
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
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens ADD token VARCHAR(255) NOT NULL, ADD expires_at INT DEFAULT NULL, ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FFF828035F37A13B ON '.$this->prefix.'oauth2_accesstokens (token)');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes ADD token VARCHAR(255) NOT NULL, ADD expires_at INT DEFAULT NULL, ADD scope VARCHAR(255) DEFAULT NULL, ADD redirect_uri LONGTEXT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3C1AB5555F37A13B ON '.$this->prefix.'oauth2_authcodes (token)');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients ADD random_id VARCHAR(255) NOT NULL, ADD secret VARCHAR(255) NOT NULL, ADD redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ADD allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens ADD token VARCHAR(255) NOT NULL, ADD expires_at INT DEFAULT NULL, ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_20FE52A95F37A13B ON '.$this->prefix.'oauth2_refreshtokens (token)');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets DROP FOREIGN KEY FK_3C49AF6A25F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets DROP FOREIGN KEY FK_3C49AF6A87C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets DROP FOREIGN KEY FK_3C49AF6ADE12AB56');
        $this->addSql('DROP INDEX IDX_3C49AF6ADE12AB56 ON '.$this->prefix.'assets');
        $this->addSql('DROP INDEX IDX_3C49AF6A25F94802 ON '.$this->prefix.'assets');
        $this->addSql('DROP INDEX IDX_3C49AF6A87C0719F ON '.$this->prefix.'assets');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns DROP FOREIGN KEY FK_42B0A225F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns DROP FOREIGN KEY FK_42B0A287C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns DROP FOREIGN KEY FK_42B0A2DE12AB56');
        $this->addSql('DROP INDEX IDX_42B0A2DE12AB56 ON '.$this->prefix.'campaigns');
        $this->addSql('DROP INDEX IDX_42B0A225F94802 ON '.$this->prefix.'campaigns');
        $this->addSql('DROP INDEX IDX_42B0A287C0719F ON '.$this->prefix.'campaigns');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns ADD canvas_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events DROP FOREIGN KEY FK_2E942CE5727ACA70');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events DROP canvas_settings');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events ADD CONSTRAINT FK_2E942CE5727ACA70 FOREIGN KEY (parent_id) REFERENCES '.$this->prefix.'campaign_events (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories DROP FOREIGN KEY FK_52CDC45425F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories DROP FOREIGN KEY FK_52CDC45487C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories DROP FOREIGN KEY FK_52CDC454DE12AB56');
        $this->addSql('DROP INDEX IDX_52CDC454DE12AB56 ON '.$this->prefix.'categories');
        $this->addSql('DROP INDEX IDX_52CDC45425F94802 ON '.$this->prefix.'categories');
        $this->addSql('DROP INDEX IDX_52CDC45487C0719F ON '.$this->prefix.'categories');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail DROP FOREIGN KEY FK_F3DD0BC355458D');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail DROP FOREIGN KEY FK_F3DD0BC3A832C1C9');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail ADD CONSTRAINT FK_F3DD0BC355458D FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail ADD CONSTRAINT FK_F3DD0BC3A832C1C9 FOREIGN KEY (email_id) REFERENCES '.$this->prefix.'emails (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP FOREIGN KEY FK_9193AB625F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP FOREIGN KEY FK_9193AB687C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP FOREIGN KEY FK_9193AB6DE12AB56');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP FOREIGN KEY FK_9193AB691861123');
        $this->addSql('DROP INDEX IDX_9193AB6DE12AB56 ON '.$this->prefix.'emails');
        $this->addSql('DROP INDEX IDX_9193AB625F94802 ON '.$this->prefix.'emails');
        $this->addSql('DROP INDEX IDX_9193AB687C0719F ON '.$this->prefix.'emails');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT FK_9193AB691861123 FOREIGN KEY (variant_parent_id) REFERENCES '.$this->prefix.'emails (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats ADD tokens LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP FOREIGN KEY FK_BAB18ADA25F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP FOREIGN KEY FK_BAB18ADA87C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP FOREIGN KEY FK_BAB18ADADE12AB56');
        $this->addSql('DROP INDEX IDX_BAB18ADADE12AB56 ON '.$this->prefix.'forms');
        $this->addSql('DROP INDEX IDX_BAB18ADA25F94802 ON '.$this->prefix.'forms');
        $this->addSql('DROP INDEX IDX_BAB18ADA87C0719F ON '.$this->prefix.'forms');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD template VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads DROP FOREIGN KEY FK_501ED47F25F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads DROP FOREIGN KEY FK_501ED47F87C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads DROP FOREIGN KEY FK_501ED47FDE12AB56');
        $this->addSql('DROP INDEX IDX_501ED47FDE12AB56 ON '.$this->prefix.'leads');
        $this->addSql('DROP INDEX IDX_501ED47F25F94802 ON '.$this->prefix.'leads');
        $this->addSql('DROP INDEX IDX_501ED47F87C0719F ON '.$this->prefix.'leads');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD last_active DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields DROP FOREIGN KEY FK_2C83D8F125F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields DROP FOREIGN KEY FK_2C83D8F187C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields DROP FOREIGN KEY FK_2C83D8F1DE12AB56');
        $this->addSql('DROP INDEX IDX_2C83D8F1DE12AB56 ON '.$this->prefix.'lead_fields');
        $this->addSql('DROP INDEX IDX_2C83D8F125F94802 ON '.$this->prefix.'lead_fields');
        $this->addSql('DROP INDEX IDX_2C83D8F187C0719F ON '.$this->prefix.'lead_fields');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists DROP FOREIGN KEY FK_6FFD01625F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists DROP FOREIGN KEY FK_6FFD01687C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists DROP FOREIGN KEY FK_6FFD016DE12AB56');
        $this->addSql('DROP INDEX IDX_6FFD016DE12AB56 ON '.$this->prefix.'lead_lists');
        $this->addSql('DROP INDEX IDX_6FFD01625F94802 ON '.$this->prefix.'lead_lists');
        $this->addSql('DROP INDEX IDX_6FFD01687C0719F ON '.$this->prefix.'lead_lists');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes DROP FOREIGN KEY FK_FC2E93F25F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes DROP FOREIGN KEY FK_FC2E93F87C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes DROP FOREIGN KEY FK_FC2E93FDE12AB56');
        $this->addSql('DROP INDEX IDX_FC2E93FDE12AB56 ON '.$this->prefix.'lead_notes');
        $this->addSql('DROP INDEX IDX_FC2E93F25F94802 ON '.$this->prefix.'lead_notes');
        $this->addSql('DROP INDEX IDX_FC2E93F87C0719F ON '.$this->prefix.'lead_notes');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_hits ADD url_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP FOREIGN KEY FK_67FA745825F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP FOREIGN KEY FK_67FA745887C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP FOREIGN KEY FK_67FA7458DE12AB56');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP FOREIGN KEY FK_67FA74589091A2FB');
        $this->addSql('DROP INDEX IDX_67FA7458DE12AB56 ON '.$this->prefix.'pages');
        $this->addSql('DROP INDEX IDX_67FA745825F94802 ON '.$this->prefix.'pages');
        $this->addSql('DROP INDEX IDX_67FA745887C0719F ON '.$this->prefix.'pages');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT FK_67FA74589091A2FB FOREIGN KEY (translation_parent_id) REFERENCES '.$this->prefix.'pages (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects DROP FOREIGN KEY FK_D67FFFFA25F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects DROP FOREIGN KEY FK_D67FFFFA87C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects DROP FOREIGN KEY FK_D67FFFFADE12AB56');
        $this->addSql('DROP INDEX IDX_D67FFFFADE12AB56 ON '.$this->prefix.'page_redirects');
        $this->addSql('DROP INDEX IDX_D67FFFFA25F94802 ON '.$this->prefix.'page_redirects');
        $this->addSql('DROP INDEX IDX_D67FFFFA87C0719F ON '.$this->prefix.'page_redirects');
        $this->addSql('ALTER TABLE '.$this->prefix.'points DROP FOREIGN KEY FK_62225CCD25F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'points DROP FOREIGN KEY FK_62225CCD87C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'points DROP FOREIGN KEY FK_62225CCDDE12AB56');
        $this->addSql('DROP INDEX IDX_62225CCDDE12AB56 ON '.$this->prefix.'points');
        $this->addSql('DROP INDEX IDX_62225CCD25F94802 ON '.$this->prefix.'points');
        $this->addSql('DROP INDEX IDX_62225CCD87C0719F ON '.$this->prefix.'points');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers DROP FOREIGN KEY FK_C64BA9CF25F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers DROP FOREIGN KEY FK_C64BA9CF87C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers DROP FOREIGN KEY FK_C64BA9CFDE12AB56');
        $this->addSql('DROP INDEX IDX_C64BA9CFDE12AB56 ON '.$this->prefix.'point_triggers');
        $this->addSql('DROP INDEX IDX_C64BA9CF25F94802 ON '.$this->prefix.'point_triggers');
        $this->addSql('DROP INDEX IDX_C64BA9CF87C0719F ON '.$this->prefix.'point_triggers');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP FOREIGN KEY FK_563D19F625F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP FOREIGN KEY FK_563D19F687C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP FOREIGN KEY FK_563D19F6DE12AB56');
        $this->addSql('DROP INDEX IDX_563D19F6DE12AB56 ON '.$this->prefix.'reports');
        $this->addSql('DROP INDEX IDX_563D19F625F94802 ON '.$this->prefix.'reports');
        $this->addSql('DROP INDEX IDX_563D19F687C0719F ON '.$this->prefix.'reports');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD table_order LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', ADD graphs LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE columns columns LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE filters filters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE title name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles DROP FOREIGN KEY FK_F1B0BFEA25F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles DROP FOREIGN KEY FK_F1B0BFEA87C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles DROP FOREIGN KEY FK_F1B0BFEADE12AB56');
        $this->addSql('DROP INDEX IDX_F1B0BFEADE12AB56 ON '.$this->prefix.'roles');
        $this->addSql('DROP INDEX IDX_F1B0BFEA25F94802 ON '.$this->prefix.'roles');
        $this->addSql('DROP INDEX IDX_F1B0BFEA87C0719F ON '.$this->prefix.'roles');
        $this->addSql('ALTER TABLE '.$this->prefix.'users DROP FOREIGN KEY FK_530D34C425F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'users DROP FOREIGN KEY FK_530D34C487C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'users DROP FOREIGN KEY FK_530D34C4DE12AB56');
        $this->addSql('DROP INDEX IDX_530D34C4DE12AB56 ON '.$this->prefix.'users');
        $this->addSql('DROP INDEX IDX_530D34C425F94802 ON '.$this->prefix.'users');
        $this->addSql('DROP INDEX IDX_530D34C487C0719F ON '.$this->prefix.'users');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels DROP FOREIGN KEY FK_2293CE7825F94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels DROP FOREIGN KEY FK_2293CE7887C0719F');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels DROP FOREIGN KEY FK_2293CE78DE12AB56');
        $this->addSql('DROP INDEX IDX_2293CE78DE12AB56 ON '.$this->prefix.'chat_channels');
        $this->addSql('DROP INDEX IDX_2293CE7825F94802 ON '.$this->prefix.'chat_channels');
        $this->addSql('DROP INDEX IDX_2293CE7887C0719F ON '.$this->prefix.'chat_channels');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats DROP FOREIGN KEY FK_6AE689226A7DC786');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats DROP FOREIGN KEY FK_6AE68922F8050BAA');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats ADD CONSTRAINT FK_6AE689226A7DC786 FOREIGN KEY (to_user) REFERENCES '.$this->prefix.'users (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats ADD CONSTRAINT FK_6AE68922F8050BAA FOREIGN KEY (from_user) REFERENCES '.$this->prefix.'users (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD storage_location VARCHAR(255) NOT NULL, ADD remote_path VARCHAR(255) DEFAULT NULL');
    }

    public function mysqlDown(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD CONSTRAINT FK_3C49AF6A25F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD CONSTRAINT FK_3C49AF6A87C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD CONSTRAINT FK_3C49AF6ADE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3C49AF6ADE12AB56 ON '.$this->prefix.'assets (created_by)');
        $this->addSql('CREATE INDEX IDX_3C49AF6A25F94802 ON '.$this->prefix.'assets (modified_by)');
        $this->addSql('CREATE INDEX IDX_3C49AF6A87C0719F ON '.$this->prefix.'assets (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events DROP FOREIGN KEY FK_2E942CE5727ACA70');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events ADD canvas_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events ADD CONSTRAINT FK_2E942CE5727ACA70 FOREIGN KEY (parent_id) REFERENCES '.$this->prefix.'campaign_events (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns DROP canvas_settings');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns ADD CONSTRAINT FK_42B0A225F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns ADD CONSTRAINT FK_42B0A287C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns ADD CONSTRAINT FK_42B0A2DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_42B0A2DE12AB56 ON '.$this->prefix.'campaigns (created_by)');
        $this->addSql('CREATE INDEX IDX_42B0A225F94802 ON '.$this->prefix.'campaigns (modified_by)');
        $this->addSql('CREATE INDEX IDX_42B0A287C0719F ON '.$this->prefix.'campaigns (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories ADD CONSTRAINT FK_52CDC45425F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories ADD CONSTRAINT FK_52CDC45487C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories ADD CONSTRAINT FK_52CDC454DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_52CDC454DE12AB56 ON '.$this->prefix.'categories (created_by)');
        $this->addSql('CREATE INDEX IDX_52CDC45425F94802 ON '.$this->prefix.'categories (modified_by)');
        $this->addSql('CREATE INDEX IDX_52CDC45487C0719F ON '.$this->prefix.'categories (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels ADD CONSTRAINT FK_2293CE7825F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels ADD CONSTRAINT FK_2293CE7887C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels ADD CONSTRAINT FK_2293CE78DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2293CE78DE12AB56 ON '.$this->prefix.'chat_channels (created_by)');
        $this->addSql('CREATE INDEX IDX_2293CE7825F94802 ON '.$this->prefix.'chat_channels (modified_by)');
        $this->addSql('CREATE INDEX IDX_2293CE7887C0719F ON '.$this->prefix.'chat_channels (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats DROP FOREIGN KEY FK_6AE68922F8050BAA');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats DROP FOREIGN KEY FK_6AE689226A7DC786');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats ADD CONSTRAINT FK_6AE68922F8050BAA FOREIGN KEY (from_user) REFERENCES '.$this->prefix.'users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats ADD CONSTRAINT FK_6AE689226A7DC786 FOREIGN KEY (to_user) REFERENCES '.$this->prefix.'users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail DROP FOREIGN KEY FK_F3DD0BC3A832C1C9');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail DROP FOREIGN KEY FK_F3DD0BC355458D');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail ADD CONSTRAINT FK_F3DD0BC3A832C1C9 FOREIGN KEY (email_id) REFERENCES '.$this->prefix.'emails (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail ADD CONSTRAINT FK_F3DD0BC355458D FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats DROP tokens');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP FOREIGN KEY FK_9193AB691861123');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT FK_9193AB625F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT FK_9193AB687C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT FK_9193AB6DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT FK_9193AB691861123 FOREIGN KEY (variant_parent_id) REFERENCES '.$this->prefix.'emails (id)');
        $this->addSql('CREATE INDEX IDX_9193AB6DE12AB56 ON '.$this->prefix.'emails (created_by)');
        $this->addSql('CREATE INDEX IDX_9193AB625F94802 ON '.$this->prefix.'emails (modified_by)');
        $this->addSql('CREATE INDEX IDX_9193AB687C0719F ON '.$this->prefix.'emails (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP template');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT FK_BAB18ADA25F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT FK_BAB18ADA87C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT FK_BAB18ADADE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BAB18ADADE12AB56 ON '.$this->prefix.'forms (created_by)');
        $this->addSql('CREATE INDEX IDX_BAB18ADA25F94802 ON '.$this->prefix.'forms (modified_by)');
        $this->addSql('CREATE INDEX IDX_BAB18ADA87C0719F ON '.$this->prefix.'forms (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields ADD CONSTRAINT FK_2C83D8F125F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields ADD CONSTRAINT FK_2C83D8F187C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields ADD CONSTRAINT FK_2C83D8F1DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2C83D8F1DE12AB56 ON '.$this->prefix.'lead_fields (created_by)');
        $this->addSql('CREATE INDEX IDX_2C83D8F125F94802 ON '.$this->prefix.'lead_fields (modified_by)');
        $this->addSql('CREATE INDEX IDX_2C83D8F187C0719F ON '.$this->prefix.'lead_fields (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists ADD CONSTRAINT FK_6FFD01625F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists ADD CONSTRAINT FK_6FFD01687C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists ADD CONSTRAINT FK_6FFD016DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_6FFD016DE12AB56 ON '.$this->prefix.'lead_lists (created_by)');
        $this->addSql('CREATE INDEX IDX_6FFD01625F94802 ON '.$this->prefix.'lead_lists (modified_by)');
        $this->addSql('CREATE INDEX IDX_6FFD01687C0719F ON '.$this->prefix.'lead_lists (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes ADD CONSTRAINT FK_FC2E93F25F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes ADD CONSTRAINT FK_FC2E93F87C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes ADD CONSTRAINT FK_FC2E93FDE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_FC2E93FDE12AB56 ON '.$this->prefix.'lead_notes (created_by)');
        $this->addSql('CREATE INDEX IDX_FC2E93F25F94802 ON '.$this->prefix.'lead_notes (modified_by)');
        $this->addSql('CREATE INDEX IDX_FC2E93F87C0719F ON '.$this->prefix.'lead_notes (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads DROP last_active');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD CONSTRAINT FK_501ED47F25F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD CONSTRAINT FK_501ED47F87C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD CONSTRAINT FK_501ED47FDE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_501ED47FDE12AB56 ON '.$this->prefix.'leads (created_by)');
        $this->addSql('CREATE INDEX IDX_501ED47F25F94802 ON '.$this->prefix.'leads (modified_by)');
        $this->addSql('CREATE INDEX IDX_501ED47F87C0719F ON '.$this->prefix.'leads (checked_out_by)');
        $this->addSql('DROP INDEX UNIQ_FFF828035F37A13B ON '.$this->prefix.'oauth2_accesstokens');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens DROP token, DROP expires_at, DROP scope');
        $this->addSql('DROP INDEX UNIQ_3C1AB5555F37A13B ON '.$this->prefix.'oauth2_authcodes');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes DROP token, DROP expires_at, DROP scope, DROP redirect_uri');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients DROP random_id, DROP secret, DROP redirect_uris, DROP allowed_grant_types');
        $this->addSql('DROP INDEX UNIQ_20FE52A95F37A13B ON '.$this->prefix.'oauth2_refreshtokens');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens DROP token, DROP expires_at, DROP scope');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_hits DROP url_title');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects ADD CONSTRAINT FK_D67FFFFA25F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects ADD CONSTRAINT FK_D67FFFFA87C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects ADD CONSTRAINT FK_D67FFFFADE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D67FFFFADE12AB56 ON '.$this->prefix.'page_redirects (created_by)');
        $this->addSql('CREATE INDEX IDX_D67FFFFA25F94802 ON '.$this->prefix.'page_redirects (modified_by)');
        $this->addSql('CREATE INDEX IDX_D67FFFFA87C0719F ON '.$this->prefix.'page_redirects (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP FOREIGN KEY FK_67FA74589091A2FB');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT FK_67FA745825F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT FK_67FA745887C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT FK_67FA7458DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT FK_67FA74589091A2FB FOREIGN KEY (translation_parent_id) REFERENCES '.$this->prefix.'pages (id)');
        $this->addSql('CREATE INDEX IDX_67FA7458DE12AB56 ON '.$this->prefix.'pages (created_by)');
        $this->addSql('CREATE INDEX IDX_67FA745825F94802 ON '.$this->prefix.'pages (modified_by)');
        $this->addSql('CREATE INDEX IDX_67FA745887C0719F ON '.$this->prefix.'pages (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers ADD CONSTRAINT FK_C64BA9CF25F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers ADD CONSTRAINT FK_C64BA9CF87C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers ADD CONSTRAINT FK_C64BA9CFDE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C64BA9CFDE12AB56 ON '.$this->prefix.'point_triggers (created_by)');
        $this->addSql('CREATE INDEX IDX_C64BA9CF25F94802 ON '.$this->prefix.'point_triggers (modified_by)');
        $this->addSql('CREATE INDEX IDX_C64BA9CF87C0719F ON '.$this->prefix.'point_triggers (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'points ADD CONSTRAINT FK_62225CCD25F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'points ADD CONSTRAINT FK_62225CCD87C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'points ADD CONSTRAINT FK_62225CCDDE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_62225CCDDE12AB56 ON '.$this->prefix.'points (created_by)');
        $this->addSql('CREATE INDEX IDX_62225CCD25F94802 ON '.$this->prefix.'points (modified_by)');
        $this->addSql('CREATE INDEX IDX_62225CCD87C0719F ON '.$this->prefix.'points (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP table_order, DROP graphs, CHANGE columns columns LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE filters filters LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE name title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD CONSTRAINT FK_563D19F625F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD CONSTRAINT FK_563D19F687C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD CONSTRAINT FK_563D19F6DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_563D19F6DE12AB56 ON '.$this->prefix.'reports (created_by)');
        $this->addSql('CREATE INDEX IDX_563D19F625F94802 ON '.$this->prefix.'reports (modified_by)');
        $this->addSql('CREATE INDEX IDX_563D19F687C0719F ON '.$this->prefix.'reports (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles ADD CONSTRAINT FK_F1B0BFEA25F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles ADD CONSTRAINT FK_F1B0BFEA87C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles ADD CONSTRAINT FK_F1B0BFEADE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F1B0BFEADE12AB56 ON '.$this->prefix.'roles (created_by)');
        $this->addSql('CREATE INDEX IDX_F1B0BFEA25F94802 ON '.$this->prefix.'roles (modified_by)');
        $this->addSql('CREATE INDEX IDX_F1B0BFEA87C0719F ON '.$this->prefix.'roles (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'users ADD CONSTRAINT FK_530D34C425F94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'users ADD CONSTRAINT FK_530D34C487C0719F FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'users ADD CONSTRAINT FK_530D34C4DE12AB56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_530D34C4DE12AB56 ON '.$this->prefix.'users (created_by)');
        $this->addSql('CREATE INDEX IDX_530D34C425F94802 ON '.$this->prefix.'users (modified_by)');
        $this->addSql('CREATE INDEX IDX_530D34C487C0719F ON '.$this->prefix.'users (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets DROP storage_location, DROP remote_path');
    }

    public function postgresUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens ADD token VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens ADD expires_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FFF828035F37A13B ON '.$this->prefix.'oauth2_accesstokens (token)');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes ADD token VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes ADD expires_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes ADD redirect_uri TEXT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3C1AB5555F37A13B ON '.$this->prefix.'oauth2_authcodes (token)');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients ADD random_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients ADD secret VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients ADD redirect_uris TEXT NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients ADD allowed_grant_types TEXT NOT NULL');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'oauth2_clients.redirect_uris IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'oauth2_clients.allowed_grant_types IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens ADD token VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens ADD expires_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_20FE52A95F37A13B ON '.$this->prefix.'oauth2_refreshtokens (token)');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets DROP CONSTRAINT fk_3c49af6ade12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets DROP CONSTRAINT fk_3c49af6a25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets DROP CONSTRAINT fk_3c49af6a87c0719f');
        $this->addSql('DROP INDEX idx_3c49af6a87c0719f');
        $this->addSql('DROP INDEX idx_3c49af6a25f94802');
        $this->addSql('DROP INDEX idx_3c49af6ade12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns DROP CONSTRAINT fk_42b0a2de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns DROP CONSTRAINT fk_42b0a225f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns DROP CONSTRAINT fk_42b0a287c0719f');
        $this->addSql('DROP INDEX idx_42b0a2de12ab56');
        $this->addSql('DROP INDEX idx_42b0a225f94802');
        $this->addSql('DROP INDEX idx_42b0a287c0719f');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns ADD canvas_settings TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'campaigns.canvas_settings IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events DROP CONSTRAINT FK_2E942CE5727ACA70');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events DROP canvas_settings');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events ADD CONSTRAINT FK_2E942CE5727ACA70 FOREIGN KEY (parent_id) REFERENCES '.$this->prefix.'campaign_events (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories DROP CONSTRAINT fk_52cdc454de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories DROP CONSTRAINT fk_52cdc45425f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories DROP CONSTRAINT fk_52cdc45487c0719f');
        $this->addSql('DROP INDEX idx_52cdc45487c0719f');
        $this->addSql('DROP INDEX idx_52cdc45425f94802');
        $this->addSql('DROP INDEX idx_52cdc454de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail DROP CONSTRAINT FK_F3DD0BC3A832C1C9');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail DROP CONSTRAINT FK_F3DD0BC355458D');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail ADD CONSTRAINT FK_F3DD0BC3A832C1C9 FOREIGN KEY (email_id) REFERENCES '.$this->prefix.'emails (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail ADD CONSTRAINT FK_F3DD0BC355458D FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP CONSTRAINT fk_9193ab6de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP CONSTRAINT fk_9193ab625f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP CONSTRAINT fk_9193ab687c0719f');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP CONSTRAINT FK_9193AB691861123');
        $this->addSql('DROP INDEX idx_9193ab625f94802');
        $this->addSql('DROP INDEX idx_9193ab687c0719f');
        $this->addSql('DROP INDEX idx_9193ab6de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT FK_9193AB691861123 FOREIGN KEY (variant_parent_id) REFERENCES '.$this->prefix.'emails (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats ADD tokens TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'email_stats.tokens IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP CONSTRAINT fk_bab18adade12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP CONSTRAINT fk_bab18ada25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP CONSTRAINT fk_bab18ada87c0719f');
        $this->addSql('DROP INDEX idx_bab18adade12ab56');
        $this->addSql('DROP INDEX idx_bab18ada87c0719f');
        $this->addSql('DROP INDEX idx_bab18ada25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD template VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads DROP CONSTRAINT fk_501ed47fde12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads DROP CONSTRAINT fk_501ed47f25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads DROP CONSTRAINT fk_501ed47f87c0719f');
        $this->addSql('DROP INDEX idx_501ed47f25f94802');
        $this->addSql('DROP INDEX idx_501ed47f87c0719f');
        $this->addSql('DROP INDEX idx_501ed47fde12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD last_active TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields DROP CONSTRAINT fk_2c83d8f1de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields DROP CONSTRAINT fk_2c83d8f125f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields DROP CONSTRAINT fk_2c83d8f187c0719f');
        $this->addSql('DROP INDEX idx_2c83d8f125f94802');
        $this->addSql('DROP INDEX idx_2c83d8f1de12ab56');
        $this->addSql('DROP INDEX idx_2c83d8f187c0719f');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists DROP CONSTRAINT fk_6ffd016de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists DROP CONSTRAINT fk_6ffd01625f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists DROP CONSTRAINT fk_6ffd01687c0719f');
        $this->addSql('DROP INDEX idx_6ffd01687c0719f');
        $this->addSql('DROP INDEX idx_6ffd01625f94802');
        $this->addSql('DROP INDEX idx_6ffd016de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes DROP CONSTRAINT fk_fc2e93fde12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes DROP CONSTRAINT fk_fc2e93f25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes DROP CONSTRAINT fk_fc2e93f87c0719f');
        $this->addSql('DROP INDEX idx_fc2e93f87c0719f');
        $this->addSql('DROP INDEX idx_fc2e93f25f94802');
        $this->addSql('DROP INDEX idx_fc2e93fde12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_hits ADD url_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP CONSTRAINT fk_67fa7458de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP CONSTRAINT fk_67fa745825f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP CONSTRAINT fk_67fa745887c0719f');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP CONSTRAINT FK_67FA74589091A2FB');
        $this->addSql('DROP INDEX idx_67fa745887c0719f');
        $this->addSql('DROP INDEX idx_67fa745825f94802');
        $this->addSql('DROP INDEX idx_67fa7458de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT FK_67FA74589091A2FB FOREIGN KEY (translation_parent_id) REFERENCES '.$this->prefix.'pages (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects DROP CONSTRAINT fk_d67ffffade12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects DROP CONSTRAINT fk_d67ffffa25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects DROP CONSTRAINT fk_d67ffffa87c0719f');
        $this->addSql('DROP INDEX idx_d67ffffade12ab56');
        $this->addSql('DROP INDEX idx_d67ffffa87c0719f');
        $this->addSql('DROP INDEX idx_d67ffffa25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'points DROP CONSTRAINT fk_62225ccdde12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'points DROP CONSTRAINT fk_62225ccd25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'points DROP CONSTRAINT fk_62225ccd87c0719f');
        $this->addSql('DROP INDEX idx_62225ccd87c0719f');
        $this->addSql('DROP INDEX idx_62225ccdde12ab56');
        $this->addSql('DROP INDEX idx_62225ccd25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers DROP CONSTRAINT fk_c64ba9cfde12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers DROP CONSTRAINT fk_c64ba9cf25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers DROP CONSTRAINT fk_c64ba9cf87c0719f');
        $this->addSql('DROP INDEX idx_c64ba9cf87c0719f');
        $this->addSql('DROP INDEX idx_c64ba9cfde12ab56');
        $this->addSql('DROP INDEX idx_c64ba9cf25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP CONSTRAINT fk_563d19f6de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP CONSTRAINT fk_563d19f625f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP CONSTRAINT fk_563d19f687c0719f');
        $this->addSql('DROP INDEX idx_563d19f6de12ab56');
        $this->addSql('DROP INDEX idx_563d19f687c0719f');
        $this->addSql('DROP INDEX idx_563d19f625f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD table_order TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD graphs TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ALTER columns DROP NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ALTER filters DROP NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports RENAME COLUMN title TO name');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'reports.table_order IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'reports.graphs IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles DROP CONSTRAINT fk_f1b0bfeade12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles DROP CONSTRAINT fk_f1b0bfea25f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles DROP CONSTRAINT fk_f1b0bfea87c0719f');
        $this->addSql('DROP INDEX idx_f1b0bfea25f94802');
        $this->addSql('DROP INDEX idx_f1b0bfeade12ab56');
        $this->addSql('DROP INDEX idx_f1b0bfea87c0719f');
        $this->addSql('ALTER TABLE '.$this->prefix.'users DROP CONSTRAINT fk_530d34c4de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'users DROP CONSTRAINT fk_530d34c425f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'users DROP CONSTRAINT fk_530d34c487c0719f');
        $this->addSql('DROP INDEX idx_530d34c425f94802');
        $this->addSql('DROP INDEX idx_530d34c487c0719f');
        $this->addSql('DROP INDEX idx_530d34c4de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels DROP CONSTRAINT fk_2293ce78de12ab56');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels DROP CONSTRAINT fk_2293ce7825f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels DROP CONSTRAINT fk_2293ce7887c0719f');
        $this->addSql('DROP INDEX idx_2293ce7887c0719f');
        $this->addSql('DROP INDEX idx_2293ce78de12ab56');
        $this->addSql('DROP INDEX idx_2293ce7825f94802');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats DROP CONSTRAINT FK_6AE68922F8050BAA');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats DROP CONSTRAINT FK_6AE689226A7DC786');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats ADD CONSTRAINT FK_6AE68922F8050BAA FOREIGN KEY (from_user) REFERENCES '.$this->prefix.'users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats ADD CONSTRAINT FK_6AE689226A7DC786 FOREIGN KEY (to_user) REFERENCES '.$this->prefix.'users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD storage_location VARCHAR(255) NOT NULL, ADD remote_path VARCHAR(255) DEFAULT NULL');
    }

    public function postgresDown(Schema $schema)
    {
        $this->addSql('DROP INDEX UNIQ_FFF828035F37A13B');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens DROP token');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens DROP expires_at');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens DROP scope');
        $this->addSql('DROP INDEX UNIQ_20FE52A95F37A13B');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens DROP token');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens DROP expires_at');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens DROP scope');
        $this->addSql('DROP INDEX UNIQ_3C1AB5555F37A13B');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes DROP token');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes DROP expires_at');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes DROP scope');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes DROP redirect_uri');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns DROP canvas_settings');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns ADD CONSTRAINT fk_42b0a2de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns ADD CONSTRAINT fk_42b0a225f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns ADD CONSTRAINT fk_42b0a287c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_42b0a2de12ab56 ON '.$this->prefix.'campaigns (created_by)');
        $this->addSql('CREATE INDEX idx_42b0a225f94802 ON '.$this->prefix.'campaigns (modified_by)');
        $this->addSql('CREATE INDEX idx_42b0a287c0719f ON '.$this->prefix.'campaigns (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail DROP CONSTRAINT fk_f3dd0bc3a832c1c9');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail DROP CONSTRAINT fk_f3dd0bc355458d');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail ADD CONSTRAINT fk_f3dd0bc3a832c1c9 FOREIGN KEY (email_id) REFERENCES '.$this->prefix.'emails (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail ADD CONSTRAINT fk_f3dd0bc355458d FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats DROP tokens');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP template');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT fk_bab18adade12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT fk_bab18ada25f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT fk_bab18ada87c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_bab18adade12ab56 ON '.$this->prefix.'forms (created_by)');
        $this->addSql('CREATE INDEX idx_bab18ada87c0719f ON '.$this->prefix.'forms (checked_out_by)');
        $this->addSql('CREATE INDEX idx_bab18ada25f94802 ON '.$this->prefix.'forms (modified_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects ADD CONSTRAINT fk_d67ffffade12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects ADD CONSTRAINT fk_d67ffffa25f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects ADD CONSTRAINT fk_d67ffffa87c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d67ffffade12ab56 ON '.$this->prefix.'page_redirects (created_by)');
        $this->addSql('CREATE INDEX idx_d67ffffa87c0719f ON '.$this->prefix.'page_redirects (checked_out_by)');
        $this->addSql('CREATE INDEX idx_d67ffffa25f94802 ON '.$this->prefix.'page_redirects (modified_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels ADD CONSTRAINT fk_2293ce78de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels ADD CONSTRAINT fk_2293ce7825f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'chat_channels ADD CONSTRAINT fk_2293ce7887c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_2293ce7887c0719f ON '.$this->prefix.'chat_channels (checked_out_by)');
        $this->addSql('CREATE INDEX idx_2293ce78de12ab56 ON '.$this->prefix.'chat_channels (created_by)');
        $this->addSql('CREATE INDEX idx_2293ce7825f94802 ON '.$this->prefix.'chat_channels (modified_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers ADD CONSTRAINT fk_c64ba9cfde12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers ADD CONSTRAINT fk_c64ba9cf25f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers ADD CONSTRAINT fk_c64ba9cf87c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_c64ba9cf87c0719f ON '.$this->prefix.'point_triggers (checked_out_by)');
        $this->addSql('CREATE INDEX idx_c64ba9cfde12ab56 ON '.$this->prefix.'point_triggers (created_by)');
        $this->addSql('CREATE INDEX idx_c64ba9cf25f94802 ON '.$this->prefix.'point_triggers (modified_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails DROP CONSTRAINT fk_9193ab691861123');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT fk_9193ab6de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT fk_9193ab625f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT fk_9193ab687c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails ADD CONSTRAINT fk_9193ab691861123 FOREIGN KEY (variant_parent_id) REFERENCES '.$this->prefix.'emails (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_9193ab625f94802 ON '.$this->prefix.'emails (modified_by)');
        $this->addSql('CREATE INDEX idx_9193ab687c0719f ON '.$this->prefix.'emails (checked_out_by)');
        $this->addSql('CREATE INDEX idx_9193ab6de12ab56 ON '.$this->prefix.'emails (created_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD CONSTRAINT fk_3c49af6ade12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD CONSTRAINT fk_3c49af6a25f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD CONSTRAINT fk_3c49af6a87c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_3c49af6a87c0719f ON '.$this->prefix.'assets (checked_out_by)');
        $this->addSql('CREATE INDEX idx_3c49af6a25f94802 ON '.$this->prefix.'assets (modified_by)');
        $this->addSql('CREATE INDEX idx_3c49af6ade12ab56 ON '.$this->prefix.'assets (created_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'page_hits DROP url_title');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories ADD CONSTRAINT fk_52cdc454de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories ADD CONSTRAINT fk_52cdc45425f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'categories ADD CONSTRAINT fk_52cdc45487c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_52cdc45487c0719f ON '.$this->prefix.'categories (checked_out_by)');
        $this->addSql('CREATE INDEX idx_52cdc45425f94802 ON '.$this->prefix.'categories (modified_by)');
        $this->addSql('CREATE INDEX idx_52cdc454de12ab56 ON '.$this->prefix.'categories (created_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes ADD CONSTRAINT fk_fc2e93fde12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes ADD CONSTRAINT fk_fc2e93f25f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes ADD CONSTRAINT fk_fc2e93f87c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fc2e93f87c0719f ON '.$this->prefix.'lead_notes (checked_out_by)');
        $this->addSql('CREATE INDEX idx_fc2e93f25f94802 ON '.$this->prefix.'lead_notes (modified_by)');
        $this->addSql('CREATE INDEX idx_fc2e93fde12ab56 ON '.$this->prefix.'lead_notes (created_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages DROP CONSTRAINT fk_67fa74589091a2fb');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT fk_67fa7458de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT fk_67fa745825f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT fk_67fa745887c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD CONSTRAINT fk_67fa74589091a2fb FOREIGN KEY (translation_parent_id) REFERENCES '.$this->prefix.'pages (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_67fa745887c0719f ON '.$this->prefix.'pages (checked_out_by)');
        $this->addSql('CREATE INDEX idx_67fa745825f94802 ON '.$this->prefix.'pages (modified_by)');
        $this->addSql('CREATE INDEX idx_67fa7458de12ab56 ON '.$this->prefix.'pages (created_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients DROP random_id');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients DROP secret');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients DROP redirect_uris');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients DROP allowed_grant_types');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles ADD CONSTRAINT fk_f1b0bfeade12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles ADD CONSTRAINT fk_f1b0bfea25f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'roles ADD CONSTRAINT fk_f1b0bfea87c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_f1b0bfea25f94802 ON '.$this->prefix.'roles (modified_by)');
        $this->addSql('CREATE INDEX idx_f1b0bfeade12ab56 ON '.$this->prefix.'roles (created_by)');
        $this->addSql('CREATE INDEX idx_f1b0bfea87c0719f ON '.$this->prefix.'roles (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists ADD CONSTRAINT fk_6ffd016de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists ADD CONSTRAINT fk_6ffd01625f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists ADD CONSTRAINT fk_6ffd01687c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_6ffd01687c0719f ON '.$this->prefix.'lead_lists (checked_out_by)');
        $this->addSql('CREATE INDEX idx_6ffd01625f94802 ON '.$this->prefix.'lead_lists (modified_by)');
        $this->addSql('CREATE INDEX idx_6ffd016de12ab56 ON '.$this->prefix.'lead_lists (created_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields ADD CONSTRAINT fk_2c83d8f1de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields ADD CONSTRAINT fk_2c83d8f125f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields ADD CONSTRAINT fk_2c83d8f187c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_2c83d8f125f94802 ON '.$this->prefix.'lead_fields (modified_by)');
        $this->addSql('CREATE INDEX idx_2c83d8f1de12ab56 ON '.$this->prefix.'lead_fields (created_by)');
        $this->addSql('CREATE INDEX idx_2c83d8f187c0719f ON '.$this->prefix.'lead_fields (checked_out_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events DROP CONSTRAINT fk_2e942ce5727aca70');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events ADD canvas_settings TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events ADD CONSTRAINT fk_2e942ce5727aca70 FOREIGN KEY (parent_id) REFERENCES '.$this->prefix.'campaign_events (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'campaign_events.canvas_settings IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE '.$this->prefix.'points ADD CONSTRAINT fk_62225ccdde12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'points ADD CONSTRAINT fk_62225ccd25f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'points ADD CONSTRAINT fk_62225ccd87c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_62225ccd87c0719f ON '.$this->prefix.'points (checked_out_by)');
        $this->addSql('CREATE INDEX idx_62225ccdde12ab56 ON '.$this->prefix.'points (created_by)');
        $this->addSql('CREATE INDEX idx_62225ccd25f94802 ON '.$this->prefix.'points (modified_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'users ADD CONSTRAINT fk_530d34c4de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'users ADD CONSTRAINT fk_530d34c425f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'users ADD CONSTRAINT fk_530d34c487c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_530d34c425f94802 ON '.$this->prefix.'users (modified_by)');
        $this->addSql('CREATE INDEX idx_530d34c487c0719f ON '.$this->prefix.'users (checked_out_by)');
        $this->addSql('CREATE INDEX idx_530d34c4de12ab56 ON '.$this->prefix.'users (created_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP table_order');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports DROP graphs');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ALTER columns SET NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ALTER filters SET NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports RENAME COLUMN name TO title');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD CONSTRAINT fk_563d19f6de12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD CONSTRAINT fk_563d19f625f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'reports ADD CONSTRAINT fk_563d19f687c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_563d19f6de12ab56 ON '.$this->prefix.'reports (created_by)');
        $this->addSql('CREATE INDEX idx_563d19f687c0719f ON '.$this->prefix.'reports (checked_out_by)');
        $this->addSql('CREATE INDEX idx_563d19f625f94802 ON '.$this->prefix.'reports (modified_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads DROP last_active');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD CONSTRAINT fk_501ed47fde12ab56 FOREIGN KEY (created_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD CONSTRAINT fk_501ed47f25f94802 FOREIGN KEY (modified_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD CONSTRAINT fk_501ed47f87c0719f FOREIGN KEY (checked_out_by) REFERENCES '.$this->prefix.'users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_501ed47f25f94802 ON '.$this->prefix.'leads (modified_by)');
        $this->addSql('CREATE INDEX idx_501ed47f87c0719f ON '.$this->prefix.'leads (checked_out_by)');
        $this->addSql('CREATE INDEX idx_501ed47fde12ab56 ON '.$this->prefix.'leads (created_by)');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats DROP CONSTRAINT fk_6ae68922f8050baa');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats DROP CONSTRAINT fk_6ae689226a7dc786');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats ADD CONSTRAINT fk_6ae68922f8050baa FOREIGN KEY (from_user) REFERENCES '.$this->prefix.'users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'chats ADD CONSTRAINT fk_6ae689226a7dc786 FOREIGN KEY (to_user) REFERENCES '.$this->prefix.'users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE '.$this->prefix.'assets DROP storage_location, DROP remote_path');
    }

    /**
     * Installation did not work on MsSql and SqlLite prior to beta4 and thus migration is not needed
     */
    public function mssqlUp(Schema $schema) {}
    public function mssqlDown(Schema $schema) {}
    public function sqliteUp(Schema $schema) {}
    public function sqliteDown(Schema $schema) {}
}
