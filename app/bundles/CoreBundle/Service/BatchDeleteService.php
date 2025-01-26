<?php

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Tag;

final class BatchDeleteService
{
    public const LOAD_RESULTS_IN_CHUNKS_OF = 200;

    public function __construct(
        private readonly CorePermissions $security,
        private readonly Translator $translator,
    ) {
    }

    /**
     * Perform batch delete on entities.
     *
     * @param FormModel            $model
     * @param array<string, mixed> $postActionVars
     *
     * @return array<string, mixed>
     *                              Array of flash messages
     */
    public function batchDelete(
        MauticModelInterface $model,
        array $postActionVars,
        string $ids,
        string $searchValue,
        string $modelName,
        callable $isLocked,
    ): array {
        $flashes        = [];
        $deleteIds      = [];
        $filter         = $this->getBatchActionFilter($ids, $searchValue, $model);

        if (empty($filter)) {
            $flashes[] = ['msg' => 'mautic.core.error.ids.missing'];
        } else {
            $entities = $model->getEntities([
                'filter'           => $filter,
                'ignore_paginator' => true,
            ]);
            // If there's a mismatch between given ids and found entities,
            // flash which ids were not found.
            if (($ids = json_decode($ids)) && count($entities) !== count($ids)) {
                $idsNotFound = $this->getIdsNotFound($ids, array_keys($entities), $modelName);
                $flashes     = array_merge($flashes, $idsNotFound);
            }
            $permissionBase = $model->getPermissionBase();
            // Do this in chunks so that we don't run out of memory.
            $chunks = array_chunk($entities, self::LOAD_RESULTS_IN_CHUNKS_OF);
            foreach ($chunks as $chunk) {
                // Check if any entities cannot be deleted.
                if (method_exists($model, 'cannotBeDeleted')) {
                    $cannotBeDeleted       = $model->cannotBeDeleted(array_map(fn ($entity) => $entity->getId(), $chunk));
                    $flashes               = array_merge($flashes, $cannotBeDeleted);
                    // Filter out the entities that cannot be deleted.
                    $chunk = array_filter($chunk, fn ($entity) => !isset($cannotBeDeleted[$entity->getId()]));
                }
                // Loop over the entities to perform access checks pre-delete.
                foreach ($chunk as $entity) {
                    // In case getEntities does not return array of entities (eg: Form).
                    if (is_array($entity)) {
                        $entity = reset($entity);
                    }

                    if (!$this->checkPermission($permissionBase, $entity)) {
                        $flashes[] =  ['msg' => 'mautic.core.error.accessdenied'];
                    } elseif ($model->isLocked($entity)) {
                        // Use isLocked callback.
                        $flashes[] = $isLocked($postActionVars, $entity, $modelName, true);
                    } else {
                        $deleteIds[] = $entity->getId();
                    }
                }
                // Clear the chunk from memory after each iteration.
                unset($chunk);
            }
        }

        // Delete everything we are able to
        if (!empty($deleteIds)) {
            $entities  = $model->deleteEntities($deleteIds);
            $flashes[] = [
                'msg'     => $this->getTranslationKey($modelName, 'notice.batch_deleted'),
                'msgVars' => [
                    '%count%' => count($entities),
                ],
            ];
        }

        return $flashes;
    }

    /**
     * @return array<string, array{string?:string, force?:array<string, mixed>}>
     */
    public function getBatchActionFilter(string $ids, string $searchValue, FormModel $model): array
    {
        $filter = [];
        // When user select 'all'.
        if ('all' === $ids) {
            $filter     = ['string' => $searchValue, 'force' => []];
        }
        // When user select specific entities.
        if (json_decode($ids)) {
            $alias           = $model->getRepository()->getTableAlias();
            $filter['force'] = [[
                'column' => $alias.'.id',
                'expr'   => 'in',
                'value'  => json_decode($ids),
            ]];
        }

        return $filter;
    }

    private function checkPermission(string $permissionBase, CommonEntity|Tag $entity): bool
    {
        if (method_exists($entity, 'getCreatedBy')) {
            return $this->security->hasEntityAccess(
                $permissionBase.':deleteown',
                $permissionBase.':deleteother',
                $entity->getCreatedBy());
        }

        return $this->security->isGranted($permissionBase.':delete');
    }

    /**
     * Return flash messages for ids not found.
     *
     * @param int[] $givenIds
     * @param int[] $entityIds
     *
     * @return array<string, array{type: string, msg: string, msgVars: array<string, mixed>}>
     */
    private function getIdsNotFound(array $givenIds, array $entityIds, string $modelName): array
    {
        $flashes    = [];
        $missingIds = array_diff($givenIds, $entityIds);
        foreach ($missingIds as $id) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => $this->getTranslationKey($modelName, 'error.notfound'),
                'msgVars' => ['%id%' => $id],
            ];
        }

        return $flashes;
    }

    /**
     * Get custom or core translation.
     */
    private function getTranslationKey(string $modelName, string $action): string
    {
        $customString = 'mautic.'.$modelName.'.'.$action;

        return $this->translator->hasId($customString, 'flashes')
            ? $customString
            : 'mautic.core.'.$action;
    }
}
