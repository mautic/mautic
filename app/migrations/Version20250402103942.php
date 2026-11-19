<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250402103942 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'form_fields';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasColumn('field_width'),
            'The field field_width already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->getPrefixedTableName(self::TABLE_NAME)} ADD field_width VARCHAR(50) DEFAULT '100%' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE {$this->getPrefixedTableName(self::TABLE_NAME)} DROP field_width');
    }
}
