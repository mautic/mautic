<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201015084627 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    private $table = 'lead_fields';

    /**
     * @var string
     */
    private $transId = 'mautic.lead.field.timezone';

    /**
     * @throws DBALException
     */
    public function preUp(Schema $schema): void
    {
        $stmt = $this->getDriverStatement($this->getValue());
        $stmt->execute();
        $found = (bool) $stmt->fetch(FetchMode::ASSOCIATIVE);

        if ($found) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    /**
     * @throws DBALException
     */
    private function getDriverStatement(string $value): DriverStatement
    {
        $tableName = $this->getTableName();
        $sql       = "
            SELECT id
            FROM $tableName
            WHERE label = '$value'
        ";

        return $this->connection->prepare($sql);
    }

    private function getTableName(): string
    {
        return $this->prefix.$this->table;
    }

    private function getValue(): string
    {
        return $this->container->get('translator')->trans($this->transId);
    }

    /**
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $stmt = $this->getDriverStatement($this->transId);
        $stmt->execute();
        $row = $stmt->fetch(FetchMode::ASSOCIATIVE);

        if (!isset($row['id'])) {
            throw new SkipMigration("Row with `$this->transId` value not found");
        }

        $id        = $row['id'];
        $tableName = $this->getTableName();
        $value     = $this->getValue();
        $this->addSql("UPDATE $tableName SET label = '$value' WHERE id = $id;");
    }
}
