<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20261118090000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'users';

    private const BACKFILL_BATCH_SIZE = 500;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->getColumn('preferences')->getNotnull(),
            'Column users.preferences is already non-nullable'
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        // Existing installations may contain NULL values although the entity
        // property is typed as an array. Normalize them before tightening the
        // schema so Doctrine can hydrate every user safely. Queue bounded
        // statements instead of executing one unbounded update: migration
        // dry-runs remain read-only and large installations avoid one massive
        // row lock.
        $lastId = 0;
        do {
            $ids = $this->connection->fetchFirstColumn(
                sprintf(
                    'SELECT id FROM %s WHERE preferences IS NULL AND id > :lastId ORDER BY id ASC LIMIT %d',
                    $table->getName(),
                    self::BACKFILL_BATCH_SIZE
                ),
                ['lastId' => $lastId],
                ['lastId' => ParameterType::INTEGER]
            );

            if ([] === $ids) {
                break;
            }

            $firstId = (int) $ids[0];
            $lastId  = (int) $ids[array_key_last($ids)];
            $this->addSql(sprintf(
                "UPDATE %s SET preferences = 'a:0:{}' WHERE id BETWEEN %d AND %d AND preferences IS NULL",
                $table->getName(),
                $firstId,
                $lastId
            ));
        } while (self::BACKFILL_BATCH_SIZE === count($ids));

        $table->getColumn('preferences')->setNotnull(true);
    }
}
