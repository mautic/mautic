<?php
/**
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
class Version20170303000000 extends AbstractMauticMigration
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
        if ($table->hasIndex('date_added_country_index')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
        if (sizeof($table->getIndexes()) >= 64) {
            throw new SkipMigrationException('This table already has 64 indexes');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE INDEX {$this->prefix}date_added_country_index ON {$this->prefix}leads (date_added, country)");
        $this->addSql("CREATE INDEX {$this->prefix}date_added_index ON {$this->prefix}audit_log (date_added)");
        $this->addSql("CREATE INDEX {$this->prefix}date_hit_left_index ON {$this->prefix}page_hits (date_hit, date_left)");
    }
}
