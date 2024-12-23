<?php

namespace Mautic\CoreBundle\Model;

interface CannotBeDeletedInterface
{
    /**
     * Returns a list of entities that cannot be deleted.
     * <code>
     *    return [
     *       entityId => [
     *          'name' => 'entityName',
     *          'dependencies' => ['dependentEntityName'],
     *       ]
     *    ];
     * </code>.
     *
     * @param array $ids
     *                   Ids selected for deletion
     *
     * @return array
     *               An associative array where each key is an entity ID, and the value is an array containing:
     *               - 'name' => the entity's name
     *               - 'dependencies' => an array of entity names where the entity is used
     */
    public function cannotBeDeleted(array $ids): array;
}
