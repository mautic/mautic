<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\ListLead;

final class Version20261011120000 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getTableName())->hasIndex($this->getIndexName());
        }, sprintf('Index %s already exists', $this->getIndexName()));
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            'ALTER TABLE %s ADD INDEX %s (leadlist_id, manually_removed, lead_id)',
            $this->getTableName(),
            $this->getIndexName()
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX '.$this->getIndexName().' ON '.$this->getTableName());
    }

    private function getTableName(): string
    {
        return $this->prefix.ListLead::TABLE_NAME;
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}segment_lookup";
    }
}
