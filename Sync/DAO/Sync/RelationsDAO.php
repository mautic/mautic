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

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;

class RelationsDAO
{
    private $relations = [];

    /**
     * @param array $relations
     */
    public function addRelations(array $relations)
    {
        foreach ($relations as $relObjectName => $relation) {
            $this->addRelation($relation);
        }
    }

    /**
     * @param RelationDAO $relation
     */
    public function addRelation(RelationDao $relation)
    {
        $this->relations[] = $relation;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }
}