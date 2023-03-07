<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Throwable;

final class Version20230307105705 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getTableName())->hasIndex($this->getIndexName());
        }, sprintf('Index %s already exists', $this->getIndexName()));
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf('CREATE INDEX %s ON %s (lead_id, date_sent)', $this->getIndexName(), $this->getTableName()));
    }

    public function postUp(Schema $schema): void
    {
        try {
            $this->connection->executeStatement(sprintf('DROP INDEX %s ON %s', $this->generatePropertyName('email_stats', 'idx', ['lead_id']), $this->getTableName()));
        } catch (Throwable $e) {
        }
    }

    private function getTableName(): string
    {
        return "{$this->prefix}email_stats";
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}stat_email_lead_id_date_sent";
    }
}
