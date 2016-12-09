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
 * Class Version20160805000123.
 */
class Version20160805000123 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable("{$this->prefix}notifications")->hasIndex("{$this->prefix}notification_read_status")) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE INDEX {$this->prefix}notification_read_status ON {$this->prefix}notifications (is_read)");
        $this->addSql("CREATE INDEX {$this->prefix}notification_type ON {$this->prefix}notifications (type)");
        $this->addSql("CREATE INDEX {$this->prefix}notification_user_read_status ON {$this->prefix}notifications (is_read, user_id)");
    }
}
