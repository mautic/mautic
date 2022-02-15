<?php

declare(strict_types=1);

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
use Mautic\CoreBundle\Helper\DateTimeHelper;

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
        if ($schema->getTable("{$this->prefix}lead_lists")->hasColumn('last_built_date')) {
            throw new SkipMigration("The last_built_date column has already been added to the {$this->prefix}lead_lists table.");
        }
    }

    private function getTable(): string
    {
        return $this->prefix.'lead_lists';
    }

    public function up(Schema $schema): void
    {
        $now = (new DateTimeHelper())
            ->getUtcDateTime()
            ->format('Y-m-d H:i:s');

        $table = $this->getTable();
        $this->addSql(sprintf('ALTER TABLE `%s` ADD COLUMN `last_built_date` DATETIME NULL DEFAULT NULL AFTER `checked_out_by_user`', $table));
        $this->addSql(sprintf("UPDATE `%s` SET last_built_date = '%s'", $table, $now));
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf('ALTER TABLE `%s` DROP COLUMN `last_built_date`', $this->getTable()));
    }
}
