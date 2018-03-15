<?php

namespace Mautic\CoreBundle\Batches\Builder;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Adapter\SourceAdapterInterface;

/**
 * Builder of batch action type. Simple way how to create a batch action.
 *
 * @author David Vurbs <david.vurbs@gmail.com>
 */
interface BatchActionBuilderInterface
{
    /**
     * Set source adapter.
     *
     * @param SourceAdapterInterface $sourceAdapter
     *
     * @return static
     */
    public function setSourceAdapter(SourceAdapterInterface $sourceAdapter);

    /**
     * Set batch handler.
     *
     * @param HandlerAdapterInterface $handlerAdapter
     *
     * @return static
     */
    public function setHandlerAdapter(HandlerAdapterInterface $handlerAdapter);

    /**
     * Build a batch action.
     *
     * @return BatchActionInterface
     */
    public function build();
}