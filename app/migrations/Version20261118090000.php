<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20261118090000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'users';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->getColumn('preferences')->getNotnull(),
            'Column users.preferences is already non-nullable'
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        // Existing installations may contain NULL values although the entity
        // property is typed as an array. Normalize them before tightening the
        // schema so Doctrine can hydrate every user safely.
        $this->addSql(sprintf("UPDATE %s SET preferences = 'a:0:{}' WHERE preferences IS NULL", $table->getName()));

        $table->getColumn('preferences')->setNotnull(true);
    }
}
