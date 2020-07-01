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
class Version20181102165747 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $qb              = $this->connection->createQueryBuilder();
        $notUpdateEvents = $qb->select('ce.id')
            ->from($this->prefix.'campaign_events', 'ce')
            ->where($qb->expr()->eq('type', $qb->expr()->literal('lead.updatelead')))
            ->andWhere($qb->expr()->notLike('properties', $qb->expr()->literal('%"fields_to_update";%')))
            ->execute()->fetchColumn();
        if (empty($notUpdateEvents)) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema): void
    {
        // Check if there are even boolean fields to worry about
        $qb = $this->connection->createQueryBuilder();
        $qb->select('ce.id, ce.properties')
            ->from($this->prefix.'campaign_events', 'ce')
            ->where($qb->expr()->eq('type', $qb->expr()->literal('lead.updatelead')));
        $campaignEvents = $qb->execute()->fetchAll();
        if (count($campaignEvents)) {
            foreach ($campaignEvents as $key => $event) {
                $propertiesColumn = unserialize($event['properties']);
                $properties       = array_filter($propertiesColumn['properties']);
                // skip If fields_to_update exist
                if (isset($properties['fields_to_update'])) {
                    continue;
                }
                $newProperties                     = [];
                $fields                            = array_keys($properties);
                $newProperties['fields_to_update'] = $fields;
                $newProperties['fields']           = $properties;
                $newProperties['actions']          =  array_fill_keys($fields, 'update');
                $propertiesColumn['properties']    = $newProperties;
                $propertiesColumn                  = $propertiesColumn + $newProperties;
                $this->fixRow($qb, $event['id'], $propertiesColumn);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param              $id
     * @param              $properties
     */
    protected function fixRow(QueryBuilder $qb, $id, $properties)
    {
        $qb->resetQueryParts()
            ->update($this->prefix.'campaign_events')
            ->set('properties', $qb->expr()->literal(serialize($properties)))
            ->where(
                $qb->expr()->eq('id', $id)
            )
            ->execute();
    }
}
