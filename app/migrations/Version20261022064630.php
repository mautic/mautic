<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\UserBundle\Entity\OidcSubjectId;

final class Version20261022064630 extends PreUpAssertionMigration
{
    public function getDescription(): string
    {
        return 'Create table for OpenID subject IDs';
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->hasTable($this->prefix.OidcSubjectId::TABLE_NAME);
        }, sprintf('Table %s already exists', OidcSubjectId::TABLE_NAME));
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable($this->prefix.OidcSubjectId::TABLE_NAME);
        $table->addColumn('user_id', 'integer', ['unsigned' => true]);

        $columnOptions = [
            'length'  => 255,
            'default' => null,
            'notnull' => false,
        ];

        // utf8mb4_bin is MySQL-only. PostgreSQL string comparison is already case-sensitive.
        if (!DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform())) {
            $columnOptions['platformOptions'] = ['collation' => 'utf8mb4_bin'];
        }

        $table->addColumn('subject_id', 'string', $columnOptions);
        $table->addUniqueIndex(['subject_id'], 'openid_subject_id');
        $table->setPrimaryKey(['user_id']);
        $table->addForeignKeyConstraint($this->prefix.'users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->prefix.OidcSubjectId::TABLE_NAME);
    }
}
