<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

trait LeadBatchActionTrait
{
    /**
     * Return entity query filter for batch action based on ids.
     *
     * @return array{}|array{string?: string, force: string|list<array{column: string, expr: string, value: list<mixed>}>}
     */
    protected function getBatchActionFilter(Request $request, string $ids): array
    {
        $filter = [];

        if ('all' === $ids) {
            $session    = $request->getSession();
            $search     = (string) $session->get('mautic.lead.filter', '');
            $indexMode  = (string) $session->get('mautic.lead.indexmode', 'list');
            $filter     = ['string' => $search, 'force' => ''];
            $translator = $this->translator;
            $anonymous  = $translator->trans('mautic.lead.lead.searchcommand.isanonymous');
            $mine       = $translator->trans('mautic.core.searchcommand.ismine');

            if ('list' !== $indexMode || !str_contains($search, $anonymous)) {
                // remove anonymous leads unless requested to prevent clutter
                $filter['force'] .= " !$anonymous";
            }
            if (!$this->security->isGranted('lead:leads:viewother')) {
                $filter['force'] .= " $mine";
            }
        }

        $decodedIds = json_decode($ids, true);
        if (is_array($decodedIds) && [] !== $decodedIds) {
            $filter['force'] = [
                [
                    'column' => 'l.id',
                    'expr'   => 'in',
                    'value'  => $decodedIds,
                ],
            ];
        }

        return $filter;
    }

    /**
     * Retrieves entity ids for all listed entities.
     *
     * @return int[]
     */
    protected function getBatchActionEntityIdsForAll(Request $request): array
    {
        $filter = $this->getBatchActionFilter($request, 'all');
        // Get all entities.
        $entities = $this->getModel('lead')->getEntities([
            'filter'           => $filter,
            'ignore_paginator' => true,
        ]);

        $ids = [];
        // Do this in chunks so that we don't run out of memory.
        $chunks = array_chunk($entities, 200);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $entity) {
                $ids[] = $entity->getId();
            }
            // Clear the chunk from memory after each iteration.
            unset($chunk);
        }

        return $ids;
    }
}
