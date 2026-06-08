<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Exception\DeleteEntityDependencyException;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;

final class BatchDeleteService
{
    public const LOAD_RESULTS_IN_CHUNKS_OF = 100;

    public function __construct(
        private readonly CorePermissions $security,
        private readonly Translator $translator,
    ) {
    }

    /**
     * Perform batch delete on entities.
     *
     * @return list<array{type?: string, msg: string, msgVars?: array<string, mixed>}>
     *                                                                                 Array of flash messages
     */
    public function batchDelete(
        FormModel $model,
        BatchDeleteRequest $request,
    ): array {
        $filter = $this->getBatchActionFilter($request->getIds(), $request->getSearchValue(), $model, $request->getFilterAlias());

        if (empty($filter)) {
            return [['msg' => 'mautic.core.error.ids.missing']];
        }

        $entities                           = $this->getEntities($model, $filter, $request);
        $flashes                            = $this->getEntityLookupFlashes($request, $entities);
        [$deleteFlashes, $entitiesToDelete] = $this->getEntitiesToDelete($model, $request, $entities);

        return array_merge($flashes, $deleteFlashes, $this->deleteEntities($model, $request->getModelName(), $entitiesToDelete));
    }

    /**
     * @return array<string, array{string?:string, force?:array<string, mixed>}>
     */
    public function getBatchActionFilter(string $ids, string $searchValue, FormModel $model, ?string $filterAlias = null): array
    {
        $filter = [];
        // When user select 'all'.
        if ('all' === $ids) {
            $filter     = ['string' => $searchValue, 'force' => []];
        }
        // When user select specific entities.
        if (json_decode($ids)) {
            $alias           = $filterAlias ?? $model->getRepository()->getTableAlias();
            $filter['force'] = [[
                'column' => $alias.'.id',
                'expr'   => 'in',
                'value'  => json_decode($ids),
            ]];
        }

        return $filter;
    }

    private function checkPermission(string $permissionBase, object $entity): bool
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
     * @param array<string, array{string?:string, force?:array<string, mixed>}> $filter
     *
     * @return array<mixed>
     */
    private function getEntities(FormModel $model, array $filter, BatchDeleteRequest $request): array
    {
        return $model->getEntities(array_merge($request->getEntitiesArgs(), [
            'filter'           => $filter,
            'ignore_paginator' => true,
        ]));
    }

    /**
     * @param array<mixed> $entities
     *
     * @return list<array{type: string, msg: string, msgVars: array<string, mixed>}>
     */
    private function getEntityLookupFlashes(BatchDeleteRequest $request, array $entities): array
    {
        $ids = json_decode($request->getIds());

        if (!$ids || count($entities) === count($ids)) {
            return [];
        }

        return $this->getIdsNotFound($ids, array_keys($entities), $request->getModelName());
    }

    /**
     * @param array<mixed> $entities
     *
     * @return array{list<array{type?: string, msg: string, msgVars?: array<string, mixed>}>, list<object>}
     */
    private function getEntitiesToDelete(FormModel $model, BatchDeleteRequest $request, array $entities): array
    {
        $flashes          = [];
        $entitiesToDelete = [];
        $permissionBase   = $request->getPermissionBase() ?? $model->getPermissionBase();

        foreach (array_chunk($entities, self::LOAD_RESULTS_IN_CHUNKS_OF) as $chunk) {
            $chunk                     = array_map(fn (object|array $entity): object => $this->normalizeEntity($entity), $chunk);
            [$chunk, $cannotBeDeleted] = $this->filterCannotBeDeleted($model, $chunk);
            $flashes                   = array_merge($flashes, $cannotBeDeleted);

            foreach ($chunk as $entity) {
                $blockedFlash = $this->getDeleteBlockedFlash($model, $request, $permissionBase, $entity);

                if (null === $blockedFlash) {
                    $entitiesToDelete[] = $entity;
                } else {
                    $flashes[] = $blockedFlash;
                }
            }
        }

        return [$flashes, $entitiesToDelete];
    }

    /**
     * @param list<object> $chunk
     *
     * @return array{list<object>, list<array{type?: string, msg: string, msgVars?: array<string, mixed>}>}
     */
    private function filterCannotBeDeleted(FormModel $model, array $chunk): array
    {
        if (!method_exists($model, 'cannotBeDeleted')) {
            return [$chunk, []];
        }

        $cannotBeDeleted = $model->cannotBeDeleted(array_map(fn (object $entity): int|string|null => $this->getEntityId($entity), $chunk));

        return [
            array_values(array_filter($chunk, fn (object $entity): bool => !isset($cannotBeDeleted[$this->getEntityId($entity)]))),
            array_values($cannotBeDeleted),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getDeleteBlockedFlash(FormModel $model, BatchDeleteRequest $request, string $permissionBase, object $entity): ?array
    {
        if (!$this->checkPermission($permissionBase, $entity)) {
            return ['msg' => 'mautic.core.error.accessdenied'];
        }

        if ($model->isLocked($entity)) {
            return $request->getLockedFlash($entity);
        }

        return null;
    }

    /**
     * @param list<object> $entitiesToDelete
     *
     * @return list<array{type?: string, msg: string, msgVars?: array<string, mixed>}>
     */
    private function deleteEntities(FormModel $model, string $modelName, array $entitiesToDelete): array
    {
        if ([] === $entitiesToDelete) {
            return [];
        }

        $flashes         = [];
        $deletedEntities = [];

        foreach ($entitiesToDelete as $entity) {
            try {
                $model->deleteEntity($entity);
                $deletedEntities[] = $entity;
            } catch (DeleteEntityDependencyException $exception) {
                foreach ($exception->getErrors() as $error) {
                    $flashes[] = [
                        'type' => 'error',
                        'msg'  => $error,
                    ];
                }
            }
        }

        if ([] === $deletedEntities) {
            return $flashes;
        }

        $flashes[] = [
            'msg'     => $this->getTranslationKey($modelName, 'notice.batch_deleted'),
            'msgVars' => [
                '%count%' => count($deletedEntities),
            ],
        ];

        return $flashes;
    }

    /**
     * Some repositories return each entity inside a hydrated row array.
     *
     * @param object|array<mixed> $entity
     */
    private function normalizeEntity(object|array $entity): object
    {
        if (is_array($entity)) {
            $entity = reset($entity);
        }

        \assert(is_object($entity));

        return $entity;
    }

    private function getEntityId(object $entity): int|string|null
    {
        \assert(method_exists($entity, 'getId'));

        return $entity->getId();
    }

    /**
     * Return flash messages for ids not found.
     *
     * @param int[] $givenIds
     * @param int[] $entityIds
     *
     * @return list<array{type: string, msg: string, msgVars: array<string, mixed>}>
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
