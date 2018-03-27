<?php

namespace Mautic\LeadBundle\Batches\Group;

use Mautic\CoreBundle\Batches\Builder\BatchActionBuilder;
use Mautic\CoreBundle\Batches\Group\BatchGroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Batch action group for lead table
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
class LeadsBatchGroup implements BatchGroupInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * LeadsBatchGroup constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @see BatchGroupInterface::registerActions()
     * {@inheritdoc}
     */
    public function registerActions()
    {
        $leadSegmentsAction = (new BatchActionBuilder())
            ->setSourceAdapter($this->container->get('mautic.lead.batch.source.lead'))
            ->setHandlerAdapter($this->container->get('mautic.lead.batch.handler.segments'))
            ->build();

        $leadCategoriesAction = (new BatchActionBuilder())
            ->setSourceAdapter($this->container->get('mautic.lead.batch.source.lead'))
            ->setHandlerAdapter($this->container->get('mautic.lead.batch.handler.categories'))
            ->build();

        $leadChannelsAction = (new BatchActionBuilder())
            ->setSourceAdapter($this->container->get('mautic.lead.batch.source.lead'))
            ->setHandlerAdapter($this->container->get('mautic.lead.batch.handler.channels'))
            ->build();

        return [
            'batch.lead.segments'   => $leadSegmentsAction,
            'batch.lead.categories' => $leadCategoriesAction,
            'batch.lead.channels'   => $leadChannelsAction,
        ];
    }
}