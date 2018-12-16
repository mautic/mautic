<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181008234543 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable(MAUTIC_TABLE_PREFIX.'form_fields')->hasColumn('validation')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE {$this->prefix}form_fields ADD validation LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)';"
        );
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        // Check if there are even boolean fields to worry about
        $qb = $this->connection->createQueryBuilder();
        $qb->select('ff.id, ff.properties')
            ->from($this->prefix.'form_fields', 'ff');
        $fields = $qb->execute()->fetchAll();
        if (count($fields)) {
            foreach ($fields as $key => $field) {
                $properties = unserialize($field['properties']);
                if (!empty($properties['international'])) {
                    $validation = ['international' => 1];
                    unset($properties['international']);
                    $this->fixRow($qb, $field['id'], $validation, $properties);
                }
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param              $id
     * @param              $validation
     * @param              $properties
     */
    protected function fixRow(QueryBuilder $qb, $id, $validation, $properties)
    {
        $qb->resetQueryParts()
            ->update($this->prefix.'form_fields')
            ->set('validation', $qb->expr()->literal(serialize($validation)))
            ->set('properties', $qb->expr()->literal(serialize($properties)))
            ->where(
                $qb->expr()->eq('id', $id)
            )
            ->execute();
    }
}
