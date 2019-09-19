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
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190919095312 extends AbstractMauticMigration
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
        if ($table->hasIndex("{$this->prefix}email_lead_date_read")) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("alter table {$this->prefix}email_stats add index {$this->prefix}email_lead_date_read (lead_id, date_read)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("alter table {$this->prefix}email_stats drop index {$this->prefix}email_lead_date_read (lead_id, date_read)");
    }
}