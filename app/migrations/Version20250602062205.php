<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\DynamicContentBundle\Entity\Stat;

final class Version20250602062205 extends PreUpAssertionMigration
{
    private const COLUMN_NAME   = 'token_placement';
    private const DEFAULT_VALUE = 'body';
    private const CHUNK_SIZE    = 5000;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(Stat::TABLE_NAME))->hasColumn(self::COLUMN_NAME),
            'Column '.self::COLUMN_NAME.' already exists in table '.$this->getPrefixedTableName(Stat::TABLE_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                "ALTER TABLE %s ADD %s varchar(10) NOT NULL DEFAULT '%s'",
                $this->getPrefixedTableName(Stat::TABLE_NAME),
                self::COLUMN_NAME,
                self::DEFAULT_VALUE,
            )
        );

        // Process records in batches
        $offset    = 0;
        $processed = 0;

        while (
            $rows = $this->connection->fetchAllAssociative(
                sprintf(
                    "SELECT id, sent_details FROM %s WHERE sent_details LIKE '%%tokenPlacement%%' ORDER BY id ASC LIMIT %d OFFSET %d",
                    $this->getPrefixedTableName(Stat::TABLE_NAME),
                    self::CHUNK_SIZE,
                    $offset
                )
            )
        ) {
            foreach ($rows as $row) {
                $details = unserialize($row['sent_details']);
                if (is_array($details) && isset($details['tokenPlacement'])) {
                    $tokenPlacement = $details['tokenPlacement'];
                    $this->addSql(
                        sprintf(
                            'UPDATE %s SET %s = ? WHERE id = ?',
                            $this->getPrefixedTableName(Stat::TABLE_NAME),
                            self::COLUMN_NAME
                        ),
                        [$tokenPlacement, $row['id']]
                    );
                    ++$processed;
                }
            }

            $offset += self::CHUNK_SIZE;

            // Break if we got fewer records than the chunk size (last batch)
            if (count($rows) < self::CHUNK_SIZE) {
                break;
            }
        }

        $this->write(sprintf('Updated token_placement for %d records', $processed));
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(Stat::TABLE_NAME));
        if ($table->hasColumn(self::COLUMN_NAME)) {
            $table->dropColumn(self::COLUMN_NAME);
        }
    }
}
