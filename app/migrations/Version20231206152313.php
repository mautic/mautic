<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20231206152313 extends PreUpAssertionMigration
{
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

    private function getOldLeadIndexName(): string
    {
        return $this->generatePropertyName('email_stats', 'idx', ['lead_id']);
    }

    private function getOldEmailIndexName(): string
    {
        return $this->generatePropertyName('email_stats', 'idx', ['email_id']);
    }

    protected function preUpAssertions(): void
    {
        $tableName = $this->getTableName();

        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($tableName, $this->getSentIndexName()),
            sprintf('Index %s already exists', $this->getSentIndexName())
        );

        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($tableName, $this->getIsReadIndexName()),
            sprintf('Index %s already exists', $this->getIsReadIndexName())
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();

        if (!$this->indexExists($tableName, $this->getSentIndexName())) {
            $this->createIndex($tableName, $this->getSentIndexName(), ['lead_id', 'date_sent']);
        }

        if (!$this->indexExists($tableName, $this->getIsReadIndexName())) {
            $this->createIndex($tableName, $this->getIsReadIndexName(), ['email_id', 'is_read']);
        }

        $oldIndexes = [
            $this->getOldLeadIndexName(),
            $this->getOldEmailIndexName(),
        ];

        foreach ($oldIndexes as $oldIndexName) {
            if ($this->indexExists($tableName, $oldIndexName)) {
                $this->dropIndex($tableName, $oldIndexName);
            }
        }
    }
}
