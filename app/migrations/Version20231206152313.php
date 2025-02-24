<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20231206152313 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getTableName())->hasIndex($this->getSentIndexName());
        }, sprintf('Index %s already exists', $this->getSentIndexName()));

        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getTableName())->hasIndex($this->getIsReadIndexName());
        }, sprintf('Index %s already exists', $this->getIsReadIndexName()));
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD INDEX %s (lead_id, date_sent), ADD INDEX %s (email_id, is_read)', $this->getTableName(), $this->getSentIndexName(), $this->getIsReadIndexName()));
    }

    public function postUp(Schema $schema): void
    {
        $this->dropIndex(['lead_id']);
        $this->dropIndex(['email_id']);
    }

    private function getTableName(): string
    {
        return "{$this->prefix}email_stats";
    }

    private function getSentIndexName(): string
    {
        return "{$this->prefix}stat_email_lead_id_date_sent";
    }

    private function getIsReadIndexName(): string
    {
        return "{$this->prefix}stat_email_email_id_is_read";
    }

    /**
     * @param string[] $columnNames
     */
    private function dropIndex(array $columnNames): void
    {
        try {
            $this->connection->executeStatement(sprintf('DROP INDEX %s ON %s', $this->generatePropertyName('email_stats', 'idx', $columnNames), $this->getTableName()));
        } catch (\Throwable) {
        }
    }
}
