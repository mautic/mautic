<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230321133733 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        foreach (['utm_campaign', 'utm_content', 'utm_medium', 'utm_source', 'utm_term'] as $column) {
            $this->skipAssertion(fn (Schema $schema) => $schema->getTable("{$this->prefix}asset_downloads")->hasColumn($column), "Column {$this->prefix}asset_downloads.{$column} already exists");
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}asset_downloads ADD utm_campaign VARCHAR(191) DEFAULT NULL, ADD utm_content VARCHAR(191) DEFAULT NULL, ADD utm_medium VARCHAR(191) DEFAULT NULL, ADD utm_source VARCHAR(191) DEFAULT NULL, ADD utm_term VARCHAR(191) DEFAULT
 NULL;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}asset_downloads DROP COLUMN utm_campaign, utm_content, utm_medium, utm_source, utm_term");
    }
}
