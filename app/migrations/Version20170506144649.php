<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170506144649 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}campaign_lead_event_failed_log (
    log_id INT NOT NULL,
    date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    reason LONGTEXT DEFAULT NULL,
    INDEX {$this->prefix}campaign_event_failed_date (date_added),
    PRIMARY KEY(log_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $fk = $this->generatePropertyName('campaign_lead_event_failed_log', 'fk', ['log_id']);
        $this->addSql("ALTER TABLE {$this->prefix}campaign_lead_event_failed_log ADD CONSTRAINT {$fk} FOREIGN KEY (log_id) REFERENCES {$this->prefix}campaign_lead_event_log (id) ON DELETE CASCADE;");
    }
}
