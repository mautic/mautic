<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260116120000 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM {$this->prefix}widgets WHERE type = 'upcoming.emails'");
    }

    public function down(Schema $schema): void
    {
        // Cannot restore deleted widgets as we don't have the original data
    }
}
