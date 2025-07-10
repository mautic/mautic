<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240416112112 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}campaigns")->hasColumn('version');
        }, sprintf('Column %s already exists', 'version'));
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}campaigns ADD version INT UNSIGNED DEFAULT 1 NOT NULL");
    }
}
