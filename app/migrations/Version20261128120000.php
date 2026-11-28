<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

/**
 * Moves stored Philippine provinces to the names now used in regions.json.
 */
final class Version20261128120000 extends PreUpAssertionMigration
{
    private const OLD_AND_NEW_VALUES = [
        'Batasn'             => 'Bataan',
        'Compostela Valley'  => 'Davao de Oro',
        'Davao'              => 'Davao del Norte',
        'Kalinga-Apayso'     => 'Kalinga',
        'Negroe Occidental'  => 'Negros Occidental',
        'Zamboanga Sibiguey' => 'Zamboanga Sibugay',
    ];

    private const COLUMNS = [
        'leads'     => 'state',
        'companies' => 'companystate',
    ];

    protected function preUpAssertions(): void
    {
        foreach (self::COLUMNS as $table => $column) {
            $this->skipAssertion(
                fn (Schema $schema) => !$this->connection->executeQuery(
                    "SELECT 1 FROM `{$this->getPrefixedTableName($table)}` WHERE `{$column}` IN ({$this->getOldValueList()}) LIMIT 1"
                )->fetchOne(),
                "No old Philippine province names left in {$this->getPrefixedTableName($table)}.{$column}"
            );
        }
    }

    public function up(Schema $schema): void
    {
        foreach (self::COLUMNS as $table => $column) {
            $cases = '';
            foreach (self::OLD_AND_NEW_VALUES as $old => $new) {
                $cases .= " WHEN {$this->connection->quote($old)} THEN {$this->connection->quote($new)}";
            }

            $this->addSql(
                "UPDATE `{$this->getPrefixedTableName($table)}` SET `{$column}` = CASE `{$column}`{$cases} ELSE `{$column}` END WHERE `{$column}` IN ({$this->getOldValueList()})"
            );
        }
    }

    private function getOldValueList(): string
    {
        return implode(', ', array_map(
            fn (string $value): string => $this->connection->quote($value),
            array_keys(self::OLD_AND_NEW_VALUES)
        ));
    }
}
