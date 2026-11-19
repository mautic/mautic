<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\SmsBundle\Entity\Sms;

final class Version20230301051300 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getPrefixedTableName(Sms::TABLE_NAME))->hasColumn('media');
        }, 'Column media already exists');
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(Sms::TABLE_NAME);

        $this->addSql(sprintf('ALTER TABLE %s add media JSON DEFAULT NULL;', $tableName));
        $this->addSql(sprintf("UPDATE %s SET media = '{}' WHERE media IS NULL;", $tableName));
        $this->addSql(sprintf('ALTER TABLE %s MODIFY media JSON NOT NULL;', $tableName));
    }
}
