<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250722150225 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))->hasColumn('republish_behavior'),
            'Column republish_behavior already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME));
        $table->addColumn('republish_behavior', Types::STRING, ['length' => 32, 'notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME));
        $table->dropColumn('republish_behavior');
    }
}
