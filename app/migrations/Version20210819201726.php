<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20210819201726 extends PreUpAssertionMigration
{
    public function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}permissions")->hasColumn('uuid');
        }, sprintf('Column %s already exists', 'uuid'));
    }

    public function up(Schema $schema): void
    {
        $statements   = [];
        $statements[] = "ALTER TABLE `{$this->prefix}permissions` ADD COLUMN `uuid` char(36) default NULL;";
        $statements[] = "UPDATE `{$this->prefix}permissions` SET `uuid` = UUID() WHERE `uuid` IS NULL;";

        $batchSql = implode(' ', $statements);
        $this->addSql($batchSql);
    }
}
