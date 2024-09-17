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
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}bundle_grapesjsbuilder")->hasColumn('draft_custom_mjml');
        }, sprintf('Column %s already exists', 'draft_custom_mjml'));
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}bundle_grapesjsbuilder ADD draft_custom_mjml ".Types::TEXT);
    }
}
