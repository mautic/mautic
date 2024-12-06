<?php

namespace Mautic\LeadBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

trait LeadBatchActionTrait
{
    public const LOAD_RESULTS_IN_CHUNKS_OF = 200;

    /**
     * Return entity query filter for batch action based on ids.
     *
     * @param Request $request
     *                         HTTP request
     * @param string  $ids
     *                         ids is either all or json array of ids
     *
     * @return array
     *               Filter arguments for the entity query
     */
    protected function getBatchActionFilter(Request $request, string $ids): array
    {
        $filter = [];

        if ('all' === $ids) {
            $session    = $request->getSession();
            $search     = $session->get('mautic.lead.filter', '');
            $indexMode  = $session->get('mautic.lead.indexmode', 'list');
            $filter     = ['string' => $search, 'force' => ''];
            $translator = $this->translator;
            $anonymous  = $translator->trans('mautic.lead.lead.searchcommand.isanonymous');
            $mine       = $translator->trans('mautic.core.searchcommand.ismine');

            if ('list' != $indexMode || ('list' == $indexMode && !str_contains($search, $anonymous))) {
                // remove anonymous leads unless requested to prevent clutter
                $filter['force'] .= " !$anonymous";
            }
            if (!$this->security->isGranted('lead:leads:viewother')) {
                $filter['force'] .= " $mine";
            }
        }

        if ($ids = json_decode($ids, true)) {
            $filter['force'] = [
                [
                    'column' => 'l.id',
                    'expr'   => 'in',
                    'value'  => $ids,
                ],
            ];
        }

        return $filter;
    }

    /**
     * Retrieves entity ids for all listed entities.
     *
     * @param Request $request
     *                         HTTP request
     *
     * @return array
     *               Array of entity ids
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
        $chunks = array_chunk($entities, self::LOAD_RESULTS_IN_CHUNKS_OF);
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
