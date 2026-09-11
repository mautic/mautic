<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230311195347 extends AbstractMauticMigration
{
    public const BATCH_SIZE = 1000;

    public function up(Schema $schema): void
    {
        $tableName  = MAUTIC_TABLE_PREFIX.'integration_entity';
        $columnName = 'integration';
        $value      = 'Pipedrive';

        // delete in consistent order (oldest first)
        $sql = "DELETE FROM {$tableName}
            WHERE {$columnName} = :value
            AND id IN (
                SELECT id FROM (
                    SELECT id
                    FROM {$tableName}
                    WHERE {$columnName} = :value
                    ORDER BY id ASC
                    LIMIT ".self::BATCH_SIZE.'
                ) AS subquery
            )';

        $rowCount = self::BATCH_SIZE;  // initial non-zero value to enter the loop

        while ($rowCount > 0) {
            $rowCount = $this->connection->executeStatement($sql, ['value' => $value]);
        }
    }
}
