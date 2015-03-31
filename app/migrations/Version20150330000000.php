<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20150330000000
 *
 * @package Mautic\Migrations
 */
class Version20150330000000 extends AbstractMauticMigration
{

    public function preUp(Schema $schema)
    {
        // Test to see if this migration has already been applied
        $oauthTable = $schema->getTable($this->prefix . 'oauth1_consumers');
        if ($oauthTable->hasColumn('consumer_key')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens CHANGE consumer_id consumer_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE expiresat expires_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_consumers CHANGE consumerKey consumer_key VARCHAR(255) NOT NULL, CHANGE consumerSecret consumer_secret VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_request_tokens CHANGE consumer_id consumer_id INT NOT NULL, CHANGE expiresat expires_at INT NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth2_clients CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaigns CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_events CHANGE name name VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log DROP FOREIGN KEY ' . $this->findPropertyName('campaign_lead_event_log', 'fk', '696C06D6'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('campaign_lead_event_log', 'idx', '696C06D6') . ' ON ' . $this->prefix . 'campaign_lead_event_log');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log CHANGE ipAddress_id ip_id INT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('campaign_lead_event_log', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('campaign_lead_event_log', 'idx', array('ip_id')) . ' ON ' . $this->prefix . 'campaign_lead_event_log (ip_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'ip_addresses CHANGE ip_address ip_address VARCHAR(45) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_donotemail CHANGE date_added date_added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats CHANGE retry_count retry_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_actions CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields CHANGE form_id form_id INT NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_ips_xref DROP FOREIGN KEY ' .  $this->findPropertyName('lead_ips_xref', 'fk', '0655458D'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_ips_xref ADD CONSTRAINT ' . $this->generatePropertyName('lead_ips_xref', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_notes CHANGE lead_id lead_id INT NOT NULL, CHANGE text text LONGTEXT NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log DROP FOREIGN KEY ' .  $this->findPropertyName('lead_points_change_log', 'fk', '6955458D'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log CHANGE event_name event_name VARCHAR(255) NOT NULL, CHANGE action_name action_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log ADD CONSTRAINT ' . $this->generatePropertyName('lead_points_change_log', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages CHANGE meta_description meta_description LONGTEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log DROP FOREIGN KEY ' . $this->findPropertyName('point_lead_action_log', 'fk', '696C06D6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log DROP FOREIGN KEY ' . $this->findPropertyName('point_lead_action_log', 'fk', 'C028CEA2'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('point_lead_action_log', 'idx', '696C06D6') . ' ON ' . $this->prefix . 'point_lead_action_log');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log CHANGE ipAddress_id ip_id INT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_action_log', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_action_log', 'fk', array('point_id')) . ' FOREIGN KEY (point_id) REFERENCES ' . $this->prefix . 'points (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('point_lead_action_log', 'idx', array('ip_id')) . ' ON ' . $this->prefix . 'point_lead_action_log (ip_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log DROP FOREIGN KEY ' . $this->findPropertyName('point_lead_event_log', 'fk', '696C06D6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log DROP FOREIGN KEY ' . $this->findPropertyName('point_lead_event_log', 'fk', '71F7E88B'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('point_lead_event_log', 'idx', '696C06D6') . ' ON ' . $this->prefix . 'point_lead_event_log');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log CHANGE ipAddress_id ip_id INT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_event_log' , 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_event_log' , 'fk', array('event_id')) . ' FOREIGN KEY (event_id) REFERENCES ' . $this->prefix . 'point_trigger_events (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('point_lead_event_log' , 'idx', array('ip_id')) . ' ON ' . $this->prefix . 'point_lead_event_log (ip_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'points CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_triggers CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_trigger_events CHANGE name name VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions DROP FOREIGN KEY ' . $this->findPropertyName('permissions', 'fk', 'D60322AC'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions CHANGE role_id role_id INT NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions ADD CONSTRAINT ' . $this->generatePropertyName('permissions', 'fk', array('role_id')) . ' FOREIGN KEY (role_id) REFERENCES ' . $this->prefix . 'roles (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'users CHANGE role_id role_id INT NOT NULL, CHANGE username username VARCHAR(255) NOT NULL, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE position position VARCHAR(255) DEFAULT NULL, CHANGE timezone timezone VARCHAR(255) DEFAULT NULL, CHANGE locale locale VARCHAR(255) DEFAULT NULL');
    }

    public function postgresqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens ALTER consumer_id SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens RENAME COLUMN expiresat TO expires_at');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_consumers RENAME COLUMN consumerkey TO consumer_key');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_consumers ALTER consumer_key SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_consumers RENAME COLUMN consumersecret TO consumer_secret');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_consumers ALTER consumer_secret SET NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_request_tokens ALTER consumer_id SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_request_tokens RENAME COLUMN expiresat TO expires_at');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth2_clients ALTER name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth2_clients ALTER name SET NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaigns ALTER name SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaigns ALTER name TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_events ALTER name SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_events ALTER name TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('campaign_lead_event_log' , 'fk' , '696c06d6'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('campaign_lead_event_log' , 'idx' , '696c06d6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log RENAME ipaddress_id TO ip_id');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('campaign_lead_event_log', 'fk' , array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('campaign_lead_event_log', 'idx' , array('ip_id')) . ' ON ' . $this->prefix . 'campaign_lead_event_log (ip_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'ip_addresses ALTER ip_address TYPE VARCHAR(45)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_donotemail ALTER date_added SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ALTER retry_count DROP DEFAULT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ALTER retry_count TYPE integer USING (trim(retry_count)::integer)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_actions ALTER name SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_actions ALTER name TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER form_id SET NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_ips_xref DROP CONSTRAINT ' . $this->findPropertyName('lead_ips_xref', 'fk', '0655458d'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_ips_xref ADD CONSTRAINT ' . $this->generatePropertyName('lead_ips_xref', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_notes ALTER lead_id SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_notes ALTER text TYPE TEXT');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log DROP CONSTRAINT ' . $this->findPropertyName('lead_points_change_log', 'fk', '6955458d'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log ALTER event_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log ALTER action_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log ADD CONSTRAINT ' . $this->generatePropertyName('lead_points_change_log', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages ALTER meta_description TYPE TEXT');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_action_log', 'fk', '696c06d6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_action_log', 'fk', 'c028cea2'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('point_lead_action_log', 'idx', '696c06d6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log RENAME ipaddress_id TO ip_id');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_action_log', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_action_log', 'fk', array('point_id')) . ' FOREIGN KEY (point_id) REFERENCES ' . $this->prefix . 'points (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('point_lead_action_log', 'idx', array('ip_id')) . ' ON ' . $this->prefix . 'point_lead_action_log (ip_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_event_log', 'fk', '696c06d6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_event_log', 'fk', '71f7e88b'));
        $this->addSql('DROP INDEX ' . $this->findPropertyName('point_lead_event_log', 'idx', '696c06d6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log RENAME ipaddress_id to ip_id');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_event_log', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_event_log', 'fk', array('event_id')) . ' FOREIGN KEY (event_id) REFERENCES ' . $this->prefix . 'point_trigger_events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('point_lead_event_log', 'idx', array('ip_id')). ' ON ' . $this->prefix . 'point_lead_event_log (ip_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'points ALTER name SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'points ALTER name TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_triggers ALTER name SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_triggers ALTER name TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_trigger_events ALTER name SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_trigger_events ALTER name TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions DROP CONSTRAINT ' . $this->findPropertyName('permissions', 'fk', 'd60322ac'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions ALTER role_id SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions ADD CONSTRAINT ' . $this->generatePropertyName('permissions', 'fk', array('role_id')) . ' FOREIGN KEY (role_id) REFERENCES ' . $this->prefix . 'roles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER role_id SET NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER username TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER first_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER last_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER email TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER position TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER timezone TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER locale TYPE VARCHAR(255)');
    }

    public function mssqlUp(Schema $schema)
    {
        $this->addSql('sp_RENAME \'' . $this->prefix . 'oauth1_access_tokens.expiresat\' , \'expires_at\', \'COLUMN\'');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('oauth1_access_tokens', 'idx', 'A76ED395') . '\')
                            ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens DROP CONSTRAINT ' . $this->findPropertyName('oauth1_access_tokens', 'idx', 'A76ED395') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('oauth1_access_tokens', 'idx', 'A76ED395') . ' ON ' . $this->prefix . 'oauth1_access_tokens');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens ALTER COLUMN user_id INT NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('oauth1_access_tokens', 'idx', array('user_id')) . ' ON ' . $this->prefix . 'oauth1_access_tokens (user_id)');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('oauth1_access_tokens', 'idx', '37FDBD6D') . '\')
                            ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens DROP CONSTRAINT ' . $this->findPropertyName('oauth1_access_tokens', 'idx', '37FDBD6D') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('oauth1_access_tokens', 'idx', '37FDBD6D') . ' ON ' . $this->prefix . 'oauth1_access_tokens');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens ALTER COLUMN consumer_id INT NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('oauth1_access_tokens', 'idx', array('consumer_id')) . ' ON ' . $this->prefix . 'oauth1_access_tokens (consumer_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_access_tokens ALTER COLUMN expires_at INT');

        $this->addSql('sp_RENAME \'' . $this->prefix . 'oauth1_consumers.consumerKey\' , \'consumer_key\', \'COLUMN\'');
        $this->addSql('sp_RENAME \'' . $this->prefix . 'oauth1_consumers.consumerSecret\' , \'consumer_secret\', \'COLUMN\'');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_consumers ALTER COLUMN consumer_key NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_consumers ALTER COLUMN consumer_secret NVARCHAR(255) NOT NULL');

        $this->addSql('sp_RENAME \'' . $this->prefix . 'oauth1_request_tokens.expiresat\' , \'expires_at\', \'COLUMN\'');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('oauth1_request_tokens', 'idx', 'A76ED395') . '\')
                            ALTER TABLE ' . $this->prefix . 'oauth1_request_tokens DROP CONSTRAINT ' . $this->findPropertyName('oauth1_request_tokens', 'idx', 'A76ED395') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('oauth1_request_tokens', 'idx', 'A76ED395') . ' ON ' . $this->prefix . 'oauth1_request_tokens');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_request_tokens ALTER COLUMN user_id INT NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('oauth1_request_tokens', 'idx', array('user_id')) . ' ON ' . $this->prefix . 'oauth1_request_tokens (user_id)');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('oauth1_request_tokens', 'idx', '37FDBD6D') . '\')
                            ALTER TABLE ' . $this->prefix . 'oauth1_request_tokens DROP CONSTRAINT ' . $this->findPropertyName('oauth1_request_tokens', 'idx', '37FDBD6D') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('oauth1_request_tokens', 'idx', '37FDBD6D') . ' ON ' . $this->prefix . 'oauth1_request_tokens');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_request_tokens ALTER COLUMN consumer_id INT NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('oauth1_request_tokens', 'idx', array('consumer_id')) . ' ON ' . $this->prefix . 'oauth1_access_tokens (consumer_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth1_request_tokens ALTER COLUMN expires_at INT NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth2_clients ALTER COLUMN name NVARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaigns ALTER COLUMN name NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_events ALTER COLUMN name NVARCHAR(255) NOT NULL');

        $this->addSql('sp_RENAME \'' . $this->prefix . 'campaign_lead_event_log.ipAddress_id\' , \'ip_id\', \'COLUMN\'');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('campaign_lead_event_log' , 'fk', '696C06D6'));
        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('campaign_lead_event_log' , 'idx', '696C06D6') . '\')
                            ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('campaign_lead_event_log' , 'idx', '696C06D6') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('campaign_lead_event_log' , 'idx', '696C06D6') . ' ON ' . $this->prefix . 'campaign_lead_event_log');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('campaign_lead_event_log', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('campaign_lead_event_log', 'idx', array('ip_id')) . ' ON ' . $this->prefix . 'campaign_lead_event_log (ip_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'ip_addresses ALTER COLUMN ip_address NVARCHAR(45) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_donotemail ALTER COLUMN date_added DATETIME2(6) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ALTER COLUMN retry_count INT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_actions ALTER COLUMN name NVARCHAR(255) NOT NULL');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('form_fields', 'idx', '5FF69B7D') . '\')
                            ALTER TABLE ' . $this->prefix . 'form_fields DROP CONSTRAINT ' . $this->findPropertyName('form_fields', 'idx', '5FF69B7D') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('form_fields', 'idx', '5FF69B7D') . ' ON ' . $this->prefix . 'form_fields');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN form_id INT NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('form_fields', 'idx', array('form_id')) . ' ON ' . $this->prefix . 'form_fields (form_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_ips_xref DROP CONSTRAINT ' . $this->findPropertyName('lead_ips_xref', 'fk', '0655458D'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_ips_xref ADD CONSTRAINT ' . $this->generatePropertyName('lead_ips_xref', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('lead_notes', 'idx', '1755458D') . '\')
                            ALTER TABLE ' . $this->prefix . 'lead_notes DROP CONSTRAINT ' . $this->findPropertyName('lead_notes', 'idx', '1755458D') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('lead_notes', 'idx', '1755458D') . ' ON ' . $this->prefix . 'lead_notes');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_notes ALTER COLUMN lead_id INT NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('lead_notes', 'idx', array('lead_id')) . ' ON ' . $this->prefix . 'lead_notes (lead_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log ALTER COLUMN event_name NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log ALTER COLUMN action_name NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log DROP CONSTRAINT ' . $this->findPropertyName('lead_points_change_log', 'fk', '6955458D'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_points_change_log ADD CONSTRAINT ' . $this->generatePropertyName('lead_points_change_log', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE');

        $this->addSql('sp_RENAME \'' . $this->prefix . 'point_lead_action_log.ipAddress_id\' , \'ip_id\', \'COLUMN\'');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_action_log', 'fk', '696C06D6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_action_log', 'fk', 'C028CEA2'));
        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('point_lead_action_log', 'idx', '696C06D6') . '\')
                            ALTER TABLE ' . $this->prefix . 'point_lead_action_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_action_log', 'idx', '696C06D6') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('point_lead_action_log', 'idx', '696C06D6') . ' ON ' . $this->prefix . 'point_lead_action_log');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_action_log', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_action_log', 'fk', array('point_id')) . ' FOREIGN KEY (point_id) REFERENCES ' . $this->prefix . 'points (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('point_lead_action_log', 'idx', array('ip_id')) . ' ON ' . $this->prefix . 'point_lead_action_log (ip_id)');

        $this->addSql('sp_RENAME \'' . $this->prefix . 'point_lead_event_log.ipAddress_id\' , \'ip_id\', \'COLUMN\'');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_event_log', 'fk', '696C06D6'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_event_log', 'fk', '71F7E88B'));
        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('point_lead_event_log', 'idx', '696C06D6') . '\')
                            ALTER TABLE ' . $this->prefix . 'point_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('point_lead_event_log', 'idx', '696C06D6') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('point_lead_event_log', 'idx', '696C06D6') . ' ON ' . $this->prefix . 'point_lead_event_log');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_event_log', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('point_lead_event_log', 'fk', array('event_id')) . ' FOREIGN KEY (event_id) REFERENCES ' . $this->prefix . 'point_trigger_events (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('point_lead_event_log', 'idx', array('ip_id')) . ' ON ' . $this->prefix . 'point_lead_event_log (ip_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'points ALTER COLUMN name NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_triggers ALTER COLUMN name NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_trigger_events ALTER COLUMN name NVARCHAR(255) NOT NULL');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->prefix . '﻿unique_perm\')
                            ALTER TABLE ' . $this->prefix . 'permissions DROP CONSTRAINT ' . $this->prefix . '﻿unique_perm
                        ELSE
                            DROP INDEX ' . $this->prefix . '﻿unique_perm ON ' . $this->prefix . 'permissions');
        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('permissions', 'idx', 'D60322AC') . '\')
                            ALTER TABLE ' . $this->prefix . 'permissions DROP CONSTRAINT ' . $this->findPropertyName('permissions', 'idx', 'D60322AC') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('permissions', 'idx', 'D60322AC') . ' ON ' . $this->prefix . 'permissions');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions ALTER COLUMN role_id INT NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('permissions', 'idx', array('role_id')) . ' ON ' . $this->prefix . 'permissions (role_id)');
        $this->addSql('CREATE INDEX ' . $this->prefix . '﻿unique_perm ON ' . $this->prefix . 'permissions (bundle, name, role_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions DROP CONSTRAINT ' . $this->findPropertyName('permissions', 'fk', 'D60322AC'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'permissions ADD CONSTRAINT ' . $this->generatePropertyName('permissions', 'fk', array('role_id')) . ' FOREIGN KEY (role_id) REFERENCES ' . $this->prefix . 'roles (id) ON DELETE CASCADE');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('users', 'idx', 'D60322AC') . '\')
                            ALTER TABLE ' . $this->prefix . 'users DROP CONSTRAINT ' . $this->findPropertyName('users', 'idx', 'D60322AC') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('users', 'idx', 'D60322AC') . ' ON ' . $this->prefix . 'users');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN role_id INT NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('users', 'idx', array('role_id')) . ' ON ' . $this->prefix . 'users (role_id)');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('users', 'uniq', 'F85E0677') . '\')
                            ALTER TABLE ' . $this->prefix . 'users DROP CONSTRAINT ' . $this->findPropertyName('users', 'uniq', 'F85E0677') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('users', 'uniq', 'F85E0677') . ' ON ' . $this->prefix . 'users');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN username NVARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('users', 'uniq', array('username')) . ' ON ' . $this->prefix . 'users (username)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN first_name NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN last_name NVARCHAR(255) NOT NULL');

        $this->addSql('IF EXISTS (SELECT * FROM sysobjects WHERE name = \'' . $this->findPropertyName('users', 'uniq', 'E7927C74') . '\')
                            ALTER TABLE ' . $this->prefix . 'users DROP CONSTRAINT ' . $this->findPropertyName('users', 'uniq', 'E7927C74') . '
                        ELSE
                            DROP INDEX ' . $this->findPropertyName('users', 'uniq', 'E7927C74') . ' ON ' . $this->prefix . 'users');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN email NVARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('users', 'uniq', array('email')) . ' ON ' . $this->prefix . 'users (email)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN position NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN timezone NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN locale NVARCHAR(255)');
    }
}
