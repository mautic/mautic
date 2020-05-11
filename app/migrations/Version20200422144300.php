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
     * @var array|false
     */
    private $rowsToMigrateLookup;

    /**
     * @var array|false
     */
    private $rowsToMigrateSelectMultiselect;

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
        $this->fetchLookupsToMigrate();
        $this->fetchSelectsToMigrate();

        if (false === $this->rowsToMigrateLookup && false === $this->fetchSelectsToMigrate()) {
            throw new SkipMigration('No data to migrate');
        }
    }

    public function up(Schema $schema): void
    {
        if (false !== $this->rowsToMigrateLookup) {
            $this->migrateLookups();
        }

        if (false !== $this->rowsToMigrateSelectMultiselect) {
            $this->migrateSelects();
        }
    }

    private function fetchLookupsToMigrate()
    {
        $sql = <<<SQL
            SELECT id, properties
            FROM {$this->prefix}lead_fields
            WHERE
                type = 'lookup' AND
                properties LIKE '%|%'
SQL;

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $this->rowsToMigrateLookup = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    }

    private function migrateLookups()
    {
        foreach ($this->rowsToMigrateLookup as $rowToMigrate) {
            $properties         = unserialize($rowToMigrate['properties']);
            $properties['list'] = explode('|', $properties['list']);

            $params = [
                'id'         => $rowToMigrate['id'],
                'properties' => serialize($properties),
            ];

            $this->addSql($this->getUpdateSql(), $params);
        }
    }

    private function fetchSelectsToMigrate()
    {
        $sql = <<<SQL
            SELECT id, properties
            FROM {$this->prefix}lead_fields
            WHERE
                (
                    type = 'select' OR
                    type = 'multiselect' 
                ) AND
                    properties LIKE '%|%'
SQL;

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $this->rowsToMigrateSelectMultiselect = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    }

    private function migrateSelects()
    {
        foreach ($this->rowsToMigrateSelectMultiselect as $rowToMigrate) {
            $properties   = unserialize($rowToMigrate['properties']);
            $propertyList = explode('|', $properties['list']);

            $convertedPropertyList = [];

            foreach ($propertyList as $property) {
                $convertedPropertyList[] = [
                    'label' => $property,
                    'value' => $property,
                ];
            }

            $properties['list'] = $convertedPropertyList;

            $params = [
                'id'         => $rowToMigrate['id'],
                'properties' => serialize($properties),
            ];

            $this->addSql($this->getUpdateSql(), $params);
        }
    }

    private function getUpdateSql(): string
    {
        return <<<SQL
            UPDATE {$this->prefix}lead_fields
            SET properties = :properties
            WHERE id = :id
SQL;
    }
}
