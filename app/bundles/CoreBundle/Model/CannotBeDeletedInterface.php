<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model;

interface CannotBeDeletedInterface
{
    /**
     * Returns a list of entities that cannot be deleted.
     * <code>
     *    return [
     *       entityId => [
     *         'type'    => 'error',
     *         'msg'     => 'mautic.modelName.error.cannot.delete.batch',
     *         'msgVars' => []
     *       ]
     *    ];
     * </code>.
     *
     * @param array $ids
     *                   Ids selected for deletion
     *
     * @return array
     *               An associative array where each key is an entity ID, and the value is a flash message for why entity cannot be deleted
     */
    public function cannotBeDeleted(array $ids): array;
}
