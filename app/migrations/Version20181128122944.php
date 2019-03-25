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

class Version20181128122944 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable("{$this->prefix}email_stats");
        if ($table->hasIndex("{$this->prefix}is_read_date_sent")) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}email_stats ADD KEY {$this->prefix}is_read_date_sent (is_read, date_sent)");
        $table = $schema->getTable("{$this->prefix}email_stats");
        if ($table->hasIndex("{$this->prefix}stat_email_read_search")) {
            $this->addSql("ALTER TABLE {$this->prefix}email_stats DROP KEY {$this->prefix}stat_email_read_search");
        }
    }
}
