<?php

namespace Mautic\LeadBundle\Batches\Group;

use Mautic\CoreBundle\Batches\Builder\BatchActionBuilder;
use Mautic\CoreBundle\Batches\Group\BatchGroupInterface;
use Mautic\LeadBundle\Batches\DataAdapter\LeadSourceAdapter;
use Mautic\LeadBundle\Batches\Handler\CategoriesHandlerAdapter;
use Mautic\LeadBundle\Batches\Handler\LeadListHandlerAdapter;

/**
 * Batch action group for lead table
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
class LeadsBatchGroup implements BatchGroupInterface
{
    /**
     * @see BatchGroupInterface::registerActions()
     * {@inheritdoc}
     */
    public function registerActions()
    {
        $leadSegmentsAction = (new BatchActionBuilder())
            ->setSourceAdapter(new LeadSourceAdapter())
            ->setHandlerAdapter(new LeadListHandlerAdapter())
            ->build();

        $leadCategoriesAction = (new BatchActionBuilder())
            ->setSourceAdapter(new LeadSourceAdapter())
            ->setHandlerAdapter(new CategoriesHandlerAdapter())
            ->build();

        return [
            'batch.lead.segments'   => $leadSegmentsAction,
            'batch.lead.categories' => $leadCategoriesAction,
        ];
    }
}