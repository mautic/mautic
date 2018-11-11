<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\EncryptionHelper;

class Version20181111095447 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable($this->prefix.'webhooks')->hasColumn('secret')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    public function up(Schema $schema)
    {
        $secret = EncryptionHelper::generateKey();
        $this->addSql("ALTER TABLE {$this->prefix}webhooks ADD secret VARCHAR(255) DEFAULT NULL");
        $this->addSql("UPDATE {$this->prefix}webhooks SET secret = '{$secret}' WHERE secret IS NULL;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}webhooks DROP COLUMN secret");
    }
}
