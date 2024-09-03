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

        $connection = $this->connection;
        $rowCount   = self::BATCH_SIZE;

        while ($rowCount) {
            $sql      = "DELETE FROM $tableName WHERE $columnName = :value LIMIT ".self::BATCH_SIZE;
            $rowCount = $connection->executeStatement($sql, ['value' => $value]);
        }
    }
}
