<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\ReportBundle\Entity\Scheduler;

final class Version20220418132712 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}".Scheduler::TABLE_NAME)->hasColumn('data');
        }, 'Column data already exists');
    }

    public function up(Schema $schema): void
    {
        $schema->getTable("{$this->prefix}".Scheduler::TABLE_NAME)->addColumn('data', Types::JSON);
    }
}
