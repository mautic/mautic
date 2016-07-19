<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20160719000000
 */
class Version20160719000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable(MAUTIC_TABLE_PREFIX . 'page_hits');
        if ($table->hasColumn('client_info')) {

            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}page_hits ADD COLUMN client_info longtext DEFAULT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}page_hits ADD COLUMN device varchar(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}page_hits ADD COLUMN device_brand varchar(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}page_hits ADD COLUMN device_os varchar(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}page_hits ADD COLUMN device_model longtext DEFAULT NULL");
    }
}