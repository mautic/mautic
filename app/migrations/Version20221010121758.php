<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20221010121758 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $leadsTable = $this->getPrefixedTableName('leads');

        $oldState = $this->connection->quote('Uttaranchal');
        $newState = $this->connection->quote('Uttarakhand');

        $sql = sprintf(
            'UPDATE %s SET %s = %s WHERE %s = %s',
            $leadsTable,
            $this->connection->quoteIdentifier('state'),
            $newState,
            $this->connection->quoteIdentifier('state'),
            $oldState
        );

        $this->addSql($sql);
    }
}
