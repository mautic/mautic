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
 * Class Version20160114000000
 */

class Version20160114000000 extends AbstractMauticMigration
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
        $oauthTable = $schema->getTable($this->prefix . 'form_fields');
        if ($oauthTable->hasColumn('is_auto_fill')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }
    
    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix.'form_fields ADD COLUMN is_auto_fill TINYINT(1) NOT NULL DEFAULT 0');
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD COLUMN is_auto_fill BOOLEAN NOT NULL DEFAULT 0');
    }
}
