<?php

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;

final class BatchDeleteService
{
    private array $canNotBeDeleted = [];

    public function __construct(
        private readonly CorePermissions $security,
        private readonly Translator $translator,
    ) {
    }

    /**
     * Perform batch delete on entities.
     *
     * @param FormModel $model
     *
     * @return array
     *               Array of flash messages
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

        if (empty($filter)) {
            $flashes[] = ['msg' => 'mautic.core.error.ids.missing'];
        } else {
            $entities = $model->getEntities([
                'filter'           => $filter,
                'ignore_paginator' => true,
            ]);
            $permissionBase = $model->getPermissionBase();
            // Do this in chunks so that we don't run out of memory.
            $chunks = array_chunk($entities, 200, true);
            foreach ($chunks as $chunk) {
                // Check if any entities cannot be deleted
                if (method_exists($model, 'canNotBeDeleted')) {
                    $canNotBeDeleted       = $model->canNotBeDeleted(array_keys($chunk));
                    $this->canNotBeDeleted = array_merge($this->canNotBeDeleted, $canNotBeDeleted);
                    $chunk                 = array_diff_key($chunk, $canNotBeDeleted);
                }
                // Loop over the entities to perform access checks pre-delete.
                foreach ($chunk as $entity) {
                    // In case getEntities does not return array of entities (eg: Form).
                    if (is_array($entity)) {
                        $entity = reset($entity);
                    }

                    if (!$this->security->hasEntityAccess(
                        $permissionBase.':deleteown',
                        $permissionBase.':deleteother',
                        $entity->getCreatedBy()
                    )) {
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
                'msg'     => $this->getBatchDeletedTranslatedString($modelName),
                'msgVars' => [
                    '%count%' => count($entities),
                ],
            ];
        }

        if (!empty($this->canNotBeDeleted)) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.'.$modelName.'.error.cannot.delete.batch',
                'msgVars' => ['%entities%' => implode(', ', $this->canNotBeDeleted)],
            ];
        }

        return $flashes;
    }

    /**
     * Get custom or core translation.
     */
    private function getBatchDeletedTranslatedString($modelName): string
    {
        $customString = 'mautic.'.$modelName.'.notice.batch_deleted';

        return $this->translator->hasId($customString, 'flashes')
            ? $customString
            : 'mautic.core.notice.batch_deleted';
    }
}
