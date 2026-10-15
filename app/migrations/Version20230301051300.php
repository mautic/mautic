<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
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

        $definition = $this->platform->getColumnDeclarationSQL('media', ['type' => Type::getType(Types::JSON), 'notnull' => true]);
        $this->addSql(sprintf("ALTER TABLE %s ADD %s DEFAULT ('{}')", $tableName, $definition));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN media DROP DEFAULT', $tableName));
    }
}
