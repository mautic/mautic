<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\OpenIdBundle\Entity\SubjectId;

final class Version20221122064630 extends PreUpAssertionMigration
{
    public function getDescription(): string
    {
        return 'Create table for OpenID subject IDs';
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->hasTable($this->prefix.SubjectId::TABLE_NAME);
        }, sprintf('Table %s already exists', SubjectId::TABLE_NAME));
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable($this->prefix.SubjectId::TABLE_NAME);
        $table->addColumn('user_id', 'integer', ['unsigned' => true]);
        $table->addColumn('subject_id', 'string', ['length' => 255, 'default' => null, 'notnull' => false]);
        $table->addUniqueIndex(['subject_id'], 'openid_subject_id');
        $table->setPrimaryKey(['user_id']);
        $table->addForeignKeyConstraint($this->prefix.'users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->prefix.SubjectId::TABLE_NAME);
    }
}
