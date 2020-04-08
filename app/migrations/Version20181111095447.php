<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\EncryptionHelper;

class Version20181111095447 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->prefix.'webhooks')->hasColumn('secret')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $secret = EncryptionHelper::generateKey();
        $this->addSql("ALTER TABLE {$this->prefix}webhooks ADD secret VARCHAR(255) DEFAULT NULL");
        $this->addSql("UPDATE {$this->prefix}webhooks SET secret = '{$secret}' WHERE secret IS NULL;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}webhooks DROP COLUMN secret");
    }
}
