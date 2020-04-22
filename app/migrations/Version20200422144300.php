<?php
/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20200422144300 extends AbstractMauticMigration
{
    /**
     * @var int
     */
    private $rowId;

    /**
     * @var array|false
     */
    private $rowsToMigrate;

    public function getDescription(): string
    {
        return 'Migrate mautic_lead_fields.properties to simple array';
    }

    /**
     * @throws SkipMigration
     * @throws DBALException
     */
    public function preUp(Schema $schema): void
    {
        $sql = <<<SQL
            SELECT id, properties
            FROM {$this->prefix}lead_fields
            WHERE
                (
                    type = 'lookup' OR
                    type = 'select' OR
                    type = 'multiselect' 
                ) AND
                    properties LIKE '%|%'
SQL;

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $result = $this->rowsToMigrate = $stmt->fetchAll(FetchMode::ASSOCIATIVE);

        if (false === $result) {
            throw new SkipMigration('No data to migrate');
        }
    }

    public function up(Schema $schema): void
    {
        foreach ($this->rowsToMigrate as $rowToMigrate) {
            $properties                  = unserialize($rowToMigrate['properties']);
            $convertedProperties['list'] = explode('|', $properties['list']);

            $params = [
                'id'         => $rowToMigrate['id'],
                'properties' => serialize($convertedProperties),
            ];

            $sql = "
                UPDATE {$this->prefix}lead_fields
                SET properties = :properties
                WHERE id = :id
            ";

            $this->addSql($sql, $params);
        }
    }
}
