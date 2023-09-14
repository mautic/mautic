<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230914113910 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable("{$this->prefix}campaigns")->hasColumn('priority'),
            'Column priority already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->prefix}campaigns` ADD priority INT NOT NULL DEFAULT 2");
        $this->addSql("UPDATE `{$this->prefix}campaigns` SET priority = 2");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->prefix}campaigns` DROP COLUMN priority");
    }
}
