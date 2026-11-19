<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250904111834 extends PreUpAssertionMigration
{
    /**
     * @var string
     */
    protected const TABLE_NAME = Event::TABLE_NAME;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $this->hasColumn($schema, 'date_linked')
                && $this->hasColumn($schema, 'date_added'),
            "Table {$this->getPrefixedTableName()} already has 'date_linked' and 'date_added' column"
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());
        if (!$this->hasColumn($schema, 'date_linked')) {
            $table->addColumn('date_linked', Types::DATETIME_MUTABLE)
                ->setNotnull(false);
        }

        if (!$this->hasColumn($schema, 'date_added')) {
            $table->addColumn('date_added', Types::DATETIME_MUTABLE)
                ->setDefault('1970-01-01 00:00:00');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());
        $table->dropColumn('date_linked')
            ->dropColumn('date_added');
    }

    private function hasColumn(Schema $schema, string $column): bool
    {
        return $schema->getTable($this->getPrefixedTableName())->hasColumn($column);
    }
}
