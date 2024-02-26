<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BooleanType;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211110070503 extends PreUpAssertionMigration
{
    public function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}campaigns")->getColumn('allow_restart')->getType() instanceof BooleanType;
        }, 'Column already in BOOLEAN type');
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->prefix}campaigns` MODIFY `allow_restart` BOOLEAN NOT NULL;");
    }
}
