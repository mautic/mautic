<?php
/**
 * @copyright   2017 Mautic Contributors. All rights reserved
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
 * Migration.
 */
class Version20170127205928 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'leads');
        if ($table->hasIndex(MAUTIC_TABLE_PREFIX.'timezone_search')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $curDateTime = date('Y-m-d H:i:s');
        // Check if a timezone custom field exists. If so, just make it fixed, update type
        if ($schema->getTable(MAUTIC_TABLE_PREFIX.'leads')->hasColumn('timezone')) {
            $sql = "UPDATE `{$this->prefix}lead_fields` SET is_fixed = 1, type = 'timezone', date_modified = '{$curDateTime}' WHERE alias = 'timezone'";
        } else {
            $this->addSql("ALTER TABLE {$this->prefix}leads ADD COLUMN timezone VARCHAR(255) DEFAULT NULL");
            $sql = <<<SQL
INSERT INTO `{$this->prefix}lead_fields` (`date_added`, `is_published`, `label`, `alias`, `type`, `field_group`, `default_value`, `is_required`, `is_fixed`, `is_visible`, `is_short_visible`, `is_listable`, `is_publicly_updatable`, `is_unique_identifer`, `field_order`, `properties`, `object`) 
VALUES 
  ('{$curDateTime}', 1,'Timezone', 'timezone', 'timezone', 'core', NULL , 0, 1, 1, 0, 1, 0, 0, 26, 'a:0:{}', 'lead')
SQL;
        }

        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->prefix}timezone_search ON {$this->prefix}leads (timezone)");
    }
}
