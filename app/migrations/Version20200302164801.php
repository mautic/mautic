<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20200302164801 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Replaces serialized empty_value to placeholder in form_fields.properties column';
    }

    /**
     * @throws DBALException
     */
    public function preUp(Schema $schema): void
    {
        $sql = "
            SELECT id
            FROM {$this->prefix}form_fields
            WHERE properties LIKE '%s:11:\"empty_value\"%'
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $found = (bool) $stmt->fetch(FetchMode::ASSOCIATIVE);

        if (!$found) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE {$this->prefix}form_fields
            SET properties = REPLACE(properties, 's:11:\"empty_value\"', 's:11:\"placeholder\"')
            WHERE properties LIKE '%s:11:\"empty_value\"%';
        ");
    }
}
