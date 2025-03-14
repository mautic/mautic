<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240611103824 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable("{$this->prefix}bundle_grapesjsbuilder")->hasColumn('draft_custom_mjml'),
            'Column draft_custom_mjml already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}bundle_grapesjsbuilder ADD draft_custom_mjml ".Types::TEXT);
    }
}
