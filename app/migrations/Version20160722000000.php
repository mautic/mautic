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
 * Class Version20160722000000.
 */
class Version20160722000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable(MAUTIC_TABLE_PREFIX.'leads');
        if ($table->hasColumn('preferred_locale')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
INSERT INTO `{$this->prefix}lead_fields` (`is_published`, `label`, `alias`, `type`, `field_group`, `default_value`, `is_required`, `is_fixed`, `is_visible`, `is_short_visible`, `is_listable`, `is_publicly_updatable`, `is_unique_identifer`, `field_order`, `properties`) 
VALUES 
  (1,'Preferred Locale', 'preferred_locale', 'locale', 'core', NULL , 0, 1, 1, 0, 1, 0, 0, 25, 'a:0:{}')
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}leads ADD COLUMN preferred_locale VARCHAR(255) DEFAULT NULL");
        $this->addSql("CREATE INDEX {$this->prefix}preferred_locale_search ON {$this->prefix}leads (preferred_locale)");

        // Change type for a few fields to correct a bug that wouldn't allow editing some fixed fields
        $this->addSql("UPDATE {$this->prefix}lead_fields SET type = 'text' WHERE alias IN ('company', 'city', 'zipcode')");
    }
}
