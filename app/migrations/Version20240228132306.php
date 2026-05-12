<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240228132306 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'webhook_queue';

    public function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            $table = $schema->getTable($this->getPrefixedTableName());

            return !$table->hasColumn('payload') && $table->hasColumn('retries') && $table->hasColumn('date_modified');
        }, sprintf('No need to run migration on %s columns already exist.', $this->getPrefixedTableName()));
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if ($table->hasColumn('payload')) {
            $table->dropColumn('payload');
        }
        if (!$table->hasColumn('retries')) {
            $table->addColumn('retries', Types::SMALLINT)
                ->setUnsigned(true)
                ->setDefault(0);
        }
        if (!$table->hasColumn('date_modified')) {
            $table->addColumn('date_modified', Types::DATETIME_IMMUTABLE)
                ->setNotnull(false);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if (!$table->hasColumn('payload')) {
            $table->addColumn('payload', Types::TEXT)
                ->setNotnull(false);
        }

        if ($table->hasColumn('retries')) {
            $table->dropColumn('retries');
        }
        if ($table->hasColumn('date_modified')) {
            $table->dropColumn('date_modified');
        }
    }
}
