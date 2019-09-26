<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;

class RelationsDAO
{
    private $relations = [];

    /**
     * {@inheritdoc}
     */
    public function addRelations(ObjectDAO $objectDAO, array $relations)
    {
        foreach ($relations as $relObjectName => $relation) {
            $this->addRelation($objectDAO, $relObjectName, $relation);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addRelation(ObjectDAO $objectDAO, string $fieldName, RelationDao $relation)
    {
        $this->relations[$objectDAO->getObject()][$objectDAO->getObjectId()][$fieldName] = $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationsForObjectName(string $objectName): array
    {
        return $this->relations[$objectName] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationsForObject(string $objectName, string $objectId): array
    {
        return $this->relations[$objectName][$objectId] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationsForField(string $objectName, string $objectId, string $fieldName): ?RelationDAO
    {
        return $this->relations[$objectName][$objectId][$fieldName] ?? null;
    }
}