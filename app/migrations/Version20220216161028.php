<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220216161028 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        // 1. Fix state values in leads and companies
        $stateUpdates = [
            'Val d\'Oise' => 'Val-d\'Oise',
            'Réunion'     => 'La Réunion',
        ];

        foreach ($stateUpdates as $old => $new) {
            $oldQuoted = $this->connection->quote($old);
            $newQuoted = $this->connection->quote($new);

            // leads.state
            $leadsTable = $this->getPrefixedTableName('leads');
            $this->addSql(sprintf(
                'UPDATE %s SET %s = %s WHERE %s = %s',
                $leadsTable,
                $this->connection->quoteIdentifier('state'),
                $newQuoted,
                $this->connection->quoteIdentifier('state'),
                $oldQuoted
            ));

            // companies.companystate
            $companiesTable = $this->getPrefixedTableName('companies');
            $this->addSql(sprintf(
                'UPDATE %s SET %s = %s WHERE %s = %s',
                $companiesTable,
                $this->connection->quoteIdentifier('companystate'),
                $newQuoted,
                $this->connection->quoteIdentifier('companystate'),
                $oldQuoted
            ));
        }

        // 2. Fix serialized filters in dynamic content, segments, emails
        $serializedUpdates = [
            's:6:"filter";s:10:"Val d\'Oise";' => 's:6:"filter";s:10:"Val-d\'Oise";',
            's:6:"filter";s:8:"Réunion";'      => 's:6:"filter";s:11:"La Réunion";',
        ];

        foreach ($serializedUpdates as $oldSerialized => $newSerialized) {
            $oldQuoted = $this->connection->quote($oldSerialized);
            $newQuoted = $this->connection->quote($newSerialized);

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
                    $oldQuoted,
                    $newQuoted,
                    $this->connection->quoteIdentifier($column),
                    $this->connection->quote('%'.$oldSerialized.'%')
                ));
            }
        }
    }
}
