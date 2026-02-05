<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230307083702 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $oldCountry = $this->connection->quote('Swaziland');
        $newCountry = $this->connection->quote('Eswatini');

        $leadsTable     = $this->getPrefixedTableName('leads');
        $companiesTable = $this->getPrefixedTableName('companies');

        // Update country in leads
        $this->addSql(sprintf(
            'UPDATE %s SET %s = %s WHERE %s = %s',
            $leadsTable,
            $this->connection->quoteIdentifier('country'),
            $newCountry,
            $this->connection->quoteIdentifier('country'),
            $oldCountry
        ));

        // Update companycountry in companies
        $this->addSql(sprintf(
            'UPDATE %s SET %s = %s WHERE %s = %s',
            $companiesTable,
            $this->connection->quoteIdentifier('companycountry'),
            $newCountry,
            $this->connection->quoteIdentifier('companycountry'),
            $oldCountry
        ));

        // Serialized filter updates (safe REPLACE with WHERE)
        $oldSerialized = $this->connection->quote('s:6:"filter";s:9:"Swaziland";');
        $newSerialized = $this->connection->quote('s:6:"filter";s:8:"Eswatini";');

        $tables = [
            'dynamic_content' => 'filters',
            'lead_lists'      => 'filters',
            'emails'          => 'dynamic_content',
        ];

        foreach ($tables as $table => $column) {
            $fullTable = $this->getPrefixedTableName($table);

            $this->addSql(sprintf(
                'UPDATE %s SET %s = REPLACE(%s, %s, %s) WHERE %s LIKE %s',
                $fullTable,
                $this->connection->quoteIdentifier($column),
                $this->connection->quoteIdentifier($column),
                $oldSerialized,
                $newSerialized,
                $this->connection->quoteIdentifier($column),
                $this->connection->quote('%'.$oldSerialized.'%')
            ));
        }
    }
}
