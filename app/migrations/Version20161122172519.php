<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
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
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161122172519 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $shouldRunMigration = !$schema->hasTable($this->prefix.'plugin_citrix_events'); // Please modify to your needs

        if (!$shouldRunMigration) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS {$this->prefix}plugin_citrix_events
          (
            id INT AUTO_INCREMENT NOT NULL,
            product VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            event_name VARCHAR(255) NOT NULL,
            event_desc VARCHAR(255) DEFAULT NULL,
            event_type VARCHAR(50) NOT NULL,
            event_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
            lead_id INT NOT NULL,
            INDEX {$this->prefix}citrix_event_email (product, email),
            INDEX {$this->prefix}citrix_event_name (product, event_name, event_type),
            INDEX {$this->prefix}citrix_event_type (product, event_type, event_date),
            INDEX {$this->prefix}citrix_event_product (product, email, event_type),
            INDEX {$this->prefix}citrix_event_product_name (product, email, event_type, event_name),
            INDEX {$this->prefix}citrix_event_product_name_lead (product, event_type, event_name, lead_id),
            INDEX {$this->prefix}citrix_event_product_type_lead (product, event_type, lead_id),
            INDEX {$this->prefix}citrix_event_date (event_date),
            PRIMARY KEY(id)
          ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");

        $leadFk  = $this->generatePropertyName('plugin_citrix_events', 'fk', ['lead_id']);
        $leadIdx = $this->generatePropertyName('plugin_citrix_events', 'idx', ['lead_id']);
        $this->addSql("ALTER TABLE {$this->prefix}plugin_citrix_events ADD CONSTRAINT $leadFk FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
        $this->addSql("CREATE INDEX $leadIdx ON {$this->prefix}plugin_citrix_events (lead_id)");
    }
}
