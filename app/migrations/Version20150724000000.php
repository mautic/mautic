<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * 1.1.3 - 1.1.4.
 *
 * Class Version20150724000000
 */
class Version20150724000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        // Test to see if this migration has already been applied
        $oauthTable = $schema->getTable($this->prefix.'oauth1_consumers');
        if ($oauthTable->hasColumn('consumer_key')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Check that render_style was not added by migration 20150521
        $table = $schema->getTable($this->prefix.'forms');
        if (!$table->hasColumn('render_style')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD COLUMN render_style bool DEFAULT NULL');
        }

        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_consumers CHANGE consumerKey consumer_key VARCHAR(255) NOT NULL, CHANGE consumerSecret consumer_secret VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_access_tokens DROP FOREIGN KEY '.$this->findPropertyName('oauth1_access_tokens', 'fk', '37FDBD6D'));
        $this->addSql('DROP INDEX '.$this->findPropertyName('oauth1_access_tokens', 'idx', '37FDBD6D').' ON '.$this->prefix.'oauth1_access_tokens');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_access_tokens DROP FOREIGN KEY '.$this->findPropertyName('oauth1_access_tokens', 'fk', 'A76ED395'));
        $this->addSql('DROP INDEX '.$this->findPropertyName('oauth1_access_tokens', 'idx', 'A76ED395').' ON '.$this->prefix.'oauth1_access_tokens');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_access_tokens CHANGE consumer_id consumer_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE expiresat expires_at BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_access_tokens ADD CONSTRAINT '.$this->generatePropertyName('oauth1_access_tokens', 'fk', ['consumer_id']).' FOREIGN KEY (consumer_id) REFERENCES '.$this->prefix.'oauth1_consumers (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('oauth1_access_tokens', 'idx', ['consumer_id']).' ON '.$this->prefix.'oauth1_consumers (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_access_tokens ADD CONSTRAINT '.$this->generatePropertyName('oauth1_access_tokens', 'fk', ['user_id']).' FOREIGN KEY (user_id) REFERENCES '.$this->prefix.'users (id) ON DELETE CASCADE');

        $this->addSql('CREATE INDEX '.$this->generatePropertyName('oauth1_access_tokens', 'idx', ['user_id']).' ON '.$this->prefix.'oauth1_access_tokens (user_id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_request_tokens DROP FOREIGN KEY '.$this->findPropertyName('oauth1_request_tokens', 'fk', '37FDBD6D'));
        $this->addSql('DROP INDEX '.$this->findPropertyName('oauth1_request_tokens', 'idx', '37FDBD6D').' ON '.$this->prefix.'oauth1_request_tokens');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_request_tokens CHANGE consumer_id consumer_id INT NOT NULL, CHANGE expiresat expires_at BIGINT NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth1_request_tokens ADD CONSTRAINT '.$this->generatePropertyName('oauth1_request_tokens', 'fk', ['consumer_id']).' FOREIGN KEY (consumer_id) REFERENCES '.$this->prefix.'oauth1_consumers (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('oauth1_request_tokens', 'idx', ['consumer_id']).' ON '.$this->prefix.'oauth1_consumers (id)');

        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_clients CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_accesstokens CHANGE expires_at expires_at BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_refreshtokens CHANGE expires_at expires_at BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'oauth2_authcodes CHANGE expires_at expires_at BIGINT DEFAULT NULL');

        $this->addSql('ALTER TABLE '.$this->prefix.'campaigns CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log DROP FOREIGN KEY '.$this->findPropertyName('campaign_lead_event_log', 'fk', '696C06D6'));
        $this->addSql('DROP INDEX '.$this->findPropertyName('campaign_lead_event_log', 'idx', '696C06D6').' ON '.$this->prefix.'campaign_lead_event_log');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log CHANGE ipaddress_id ip_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log ADD CONSTRAINT '.$this->generatePropertyName('campaign_lead_event_log', 'fk', ['ip_id']).' FOREIGN KEY (ip_id) REFERENCES '.$this->prefix.'ip_addresses (id)');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('campaign_lead_event_log', 'idx', ['ip_id']).' ON '.$this->prefix.'campaign_lead_event_log (ip_id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'audit_log CHANGE ip_address ip_address VARCHAR(45) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'ip_addresses CHANGE ip_address ip_address VARCHAR(45) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_donotemail CHANGE date_added date_added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'emails CHANGE name name VARCHAR(255) NOT NULL, CHANGE email_type email_type LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_assets_xref DROP FOREIGN KEY '.$this->findPropertyName('email_assets_xref', 'fk', '75DA1941'));
        $this->addSql('ALTER TABLE '.$this->prefix.'email_assets_xref DROP FOREIGN KEY '.$this->findPropertyName('email_assets_xref', 'fk', 'A832C1C9'));
        $this->addSql('ALTER TABLE '.$this->prefix.'email_assets_xref ADD CONSTRAINT '.$this->generatePropertyName('email_assets_xref', 'fk', ['asset_id']).' FOREIGN KEY (asset_id) REFERENCES '.$this->prefix.'assets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_assets_xref ADD CONSTRAINT '.$this->generatePropertyName('email_assets_xref', 'fk', ['email_id']).' FOREIGN KEY (email_id) REFERENCES '.$this->prefix.'emails (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats CHANGE retry_count retry_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'form_actions CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'form_fields DROP FOREIGN KEY '.$this->findPropertyName('form_fields', 'fk', '5FF69B7D'));
        $this->addSql('ALTER TABLE '.$this->prefix.'form_fields CHANGE form_id form_id INT NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'form_fields ADD CONSTRAINT '.$this->generatePropertyName('form_fields', 'fk', ['form_id']).' FOREIGN KEY (form_id) REFERENCES '.$this->prefix.'forms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_ips_xref DROP FOREIGN KEY '.$this->findPropertyName('lead_ips_xref', 'fk', '0655458D'));
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_ips_xref ADD CONSTRAINT '.$this->generatePropertyName('lead_ips_xref', 'fk', ['lead_id']).' FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes DROP FOREIGN KEY '.$this->findPropertyName('lead_notes', 'fk', '1755458D'));
        $this->addSql('DROP INDEX '.$this->findPropertyName('lead_notes', 'idx', '1755458D').' ON '.$this->prefix.'lead_notes');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes CHANGE lead_id lead_id INT NOT NULL, CHANGE text text LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_notes ADD CONSTRAINT '.$this->generatePropertyName('lead_notes', 'fk', ['lead_id']).' FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('lead_notes', 'idx', ['lead_id']).' ON '.$this->prefix.'leads (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_points_change_log DROP FOREIGN KEY '.$this->findPropertyName('lead_points_change_log', 'fk', '6955458D'));
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_points_change_log CHANGE event_name event_name VARCHAR(255) NOT NULL, CHANGE action_name action_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_points_change_log ADD CONSTRAINT '.$this->generatePropertyName('lead_points_change_log', 'fk', ['lead_id']).' FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages CHANGE meta_description meta_description VARCHAR(255) DEFAULT NULL, CHANGE redirect_url redirect_url VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_action_log DROP FOREIGN KEY '.$this->findPropertyName('point_lead_action_log', 'fk', '696C06D6'));
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_action_log DROP FOREIGN KEY '.$this->findPropertyName('point_lead_action_log', 'fk', 'C028CEA2'));
        $this->addSql('DROP INDEX '.$this->findPropertyName('point_lead_action_log', 'idx', '696C06D6').' ON '.$this->prefix.'point_lead_action_log');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_action_log CHANGE ipaddress_id ip_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_action_log ADD CONSTRAINT '.$this->generatePropertyName('point_lead_action_log', 'fk', ['ip_id']).' FOREIGN KEY (ip_id) REFERENCES '.$this->prefix.'ip_addresses (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_action_log ADD CONSTRAINT '.$this->generatePropertyName('point_lead_action_log', 'fk', ['point_id']).' FOREIGN KEY (point_id) REFERENCES '.$this->prefix.'points (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('point_lead_action_log', 'idx', ['ip_id']).' ON '.$this->prefix.'point_lead_action_log (ip_id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_event_log DROP FOREIGN KEY '.$this->findPropertyName('point_lead_event_log', 'fk', '696C06D6'));
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_event_log DROP FOREIGN KEY '.$this->findPropertyName('point_lead_event_log', 'fk', '71F7E88B'));
        $this->addSql('DROP INDEX '.$this->findPropertyName('point_lead_event_log', 'idx', '696C06D6').' ON '.$this->prefix.'point_lead_event_log');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_event_log CHANGE ipaddress_id ip_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_event_log ADD CONSTRAINT '.$this->generatePropertyName('point_lead_event_log', 'fk', ['ip_id']).' FOREIGN KEY (ip_id) REFERENCES '.$this->prefix.'ip_addresses (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_lead_event_log ADD CONSTRAINT '.$this->generatePropertyName('point_lead_event_log', 'fk', ['event_id']).' FOREIGN KEY (event_id) REFERENCES '.$this->prefix.'point_trigger_events (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('point_lead_event_log', 'idx', ['ip_id']).' ON '.$this->prefix.'point_lead_event_log (ip_id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'points CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_triggers CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'point_trigger_events CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'users DROP FOREIGN KEY '.$this->findPropertyName('users', 'fk', 'D60322AC'));
        $this->addSql('DROP INDEX '.$this->findPropertyName('users', 'idx', 'D60322AC').' ON '.$this->prefix.'users');
        $this->addSql('ALTER TABLE '.$this->prefix.'users CHANGE role_id role_id INT NOT NULL, CHANGE username username VARCHAR(255) NOT NULL, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE position position VARCHAR(255) DEFAULT NULL, CHANGE timezone timezone VARCHAR(255) DEFAULT NULL, CHANGE locale locale VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'users ADD CONSTRAINT '.$this->generatePropertyName('users', 'fk', ['role_id']).' FOREIGN KEY (role_id) REFERENCES '.$this->prefix.'roles (id)');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('users', 'idx', ['role_id']).' ON '.$this->prefix.'roles (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'permissions DROP FOREIGN KEY '.$this->findPropertyName('permissions', 'fk', 'D60322AC'));
        $this->addSql('ALTER TABLE '.$this->prefix.'permissions CHANGE role_id role_id INT NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'permissions ADD CONSTRAINT '.$this->generatePropertyName('permissions', 'fk', ['role_id']).' FOREIGN KEY (role_id) REFERENCES '.$this->prefix.'roles (id) ON DELETE CASCADE');
    }
}
