<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20210818090322 extends PreUpAssertionMigration
{
    public function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}roles")->hasColumn('uuid');
        }, sprintf('Column %s already exists', 'uuid'));
    }

    public function up(Schema $schema): void
    {
        $statements   = [];
        $statements[] = "ALTER TABLE `{$this->prefix}roles` ADD COLUMN `uuid` char(36) default NULL;";
        $statements[] = "UPDATE `{$this->prefix}roles` SET `uuid` = UUID() WHERE `uuid` IS NULL;";

        $batchSql = implode(' ', $statements);
        $this->addSql($batchSql);
    }
}
