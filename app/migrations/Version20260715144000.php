<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260715144000 extends PreUpAssertionMigration
{
    private const BATCH_SIZE = 1000;

    public function isTransactional(): bool
    {
        return false;
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (): bool => 0 === (int) $this->connection->fetchOne($this->getPendingLogsSql('COUNT(*)')),
            'Condition and decision logs already have non_action_path_taken set'
        );
    }

    public function up(Schema $schema): void
    {
        $logIds = $this->fetchPendingLogIds();

        while ([] !== $logIds) {
            $this->connection->executeStatement(
                sprintf('UPDATE %s SET non_action_path_taken = 0 WHERE id IN (?)', $this->getPrefixedTableName(LeadEventLog::TABLE_NAME)),
                [$logIds],
                [ArrayParameterType::INTEGER]
            );

            $logIds = $this->fetchPendingLogIds();
        }
    }

    /**
     * @return list<int>
     */
    private function fetchPendingLogIds(): array
    {
        return array_map(intval(...), $this->connection->fetchFirstColumn(
            sprintf('%s LIMIT %d', $this->getPendingLogsSql('l.id'), self::BATCH_SIZE)
        ));
    }

    private function getPendingLogsSql(string $select): string
    {
        $eventLogTable = $this->getPrefixedTableName(LeadEventLog::TABLE_NAME);
        $eventTable    = $this->getPrefixedTableName(Event::TABLE_NAME);

        return "SELECT {$select}
            FROM {$eventLogTable} l
            INNER JOIN {$eventTable} e ON e.id = l.event_id
            WHERE e.event_type IN ('condition', 'decision')
                AND l.non_action_path_taken IS NULL
                AND l.date_triggered IS NOT NULL";
    }
}
