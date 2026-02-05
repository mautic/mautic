<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230525202700 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $tableName  = $this->getPrefixedTableName('leads');
        $columnName = $this->connection->quoteIdentifier('state');

        $stateUpdates = [
            'Niederosterreich'   => 'Niederösterreich',
            'Oberosterreich'     => 'Oberösterreich',
            'Geneva'             => 'Genève',
            'Graubunden'         => 'Graubünden',
            'Neuchatel'          => 'Neuchâtel',
            'Sankt Gallen'       => 'St. Gallen',
            'Zurich'             => 'Zürich',
            'Baden-Wuerttemberg' => 'Baden-Württemberg',
            'Thueringen'         => 'Thüringen',
        ];

        foreach ($stateUpdates as $old => $new) {
            $oldQuoted = $this->connection->quote($old);
            $newQuoted = $this->connection->quote($new);

            $this->addSql(sprintf(
                'UPDATE %s SET %s = %s WHERE %s = %s',
                $tableName,
                $columnName,
                $newQuoted,
                $columnName,
                $oldQuoted
            ));
        }
    }
}
