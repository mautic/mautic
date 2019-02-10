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

class Version20180924120834 extends AbstractMauticMigration
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
        if ($table->hasIndex("{$this->prefix}email_date_read_lead")) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("alter table {$this->prefix}email_stats add key {$this->prefix}email_date_read_lead (date_read, lead_id)");
        $table = $schema->getTable("{$this->prefix}email_stats");
        if ($table->hasIndex("{$this->prefix}email_date_read")) {
            $this->addSql("alter table {$this->prefix}email_stats drop key {$this->prefix}email_date_read");
        }
    }
}
