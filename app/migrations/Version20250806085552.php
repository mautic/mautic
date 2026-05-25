<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Entity\OptimisticLockInterface;

final class Version20250806085552 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = LeadEventLog::TABLE_NAME;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn('version'),
            "Table {$this->getPrefixedTableName()} already has 'version' column"
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());
        $table->addColumn('version', Types::INTEGER)
            ->setUnsigned(true)
            ->setDefault(OptimisticLockInterface::INITIAL_VERSION);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());
        $table->dropColumn('version');
    }
}
