<?php
/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration for changing column_value to a longtext from varchar.
 */
class Version20200812180900 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable("{$this->prefix}lead_lists")->hasColumn('last_build_date')) {
            throw new SkipMigration("The last_build_date column has already been added to the {$this->prefix}lead_lists table.");
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->prefix}lead_lists` ADD COLUMN `last_build_date` DATETIME NULL DEFAULT NULL AFTER `checked_out_by_user`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}email_copies DROP COLUMN `last_build_date`");
    }
}
