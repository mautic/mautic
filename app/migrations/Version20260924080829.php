<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260924080829 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'open_id_identifiers';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName()),
            "Table {$this->getPrefixedTableName()} already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE {$this->getPrefixedTableName()} (user_id INT UNSIGNED NOT NULL, subject_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_5D6B2A23EDC87 (subject_id), PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        $this->addSql("ALTER TABLE {$this->getPrefixedTableName()} ADD CONSTRAINT FK_5D6B2AA76ED395 FOREIGN KEY (user_id) REFERENCES {$this->getPrefixedTableName('users')} (id) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName());
    }
}
