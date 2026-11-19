<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\LeadList;

final class Version20220104115633 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getPrefixedTableName(LeadList::TABLE_NAME))->hasColumn('deleted');
        }, 'Deleted column already added in '.LeadList::TABLE_NAME);
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(LeadList::TABLE_NAME))
            ->addColumn('deleted', Types::DATETIME_MUTABLE, ['notnull' => false]);
    }
}
