<?php

namespace Mautic\CoreBundle\Batches\Builder;

use Mautic\CoreBundle\Batches\Action\BatchAction;
use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Adapter\SourceAdapterInterface;

/**
 * {@inheritdoc}
 *
 * @see BatchActionBuilderInterface
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
class BatchActionBuilder implements BatchActionBuilderInterface
{
    /**
     * @var SourceAdapterInterface
     */
    private $sourceAdapter;

    /**
     * @var HandlerAdapterInterface
     */
    private $handlerAdapter;

    /**
     * @see BatchActionBuilderInterface::setSourceAdapter()
     * {@inheritdoc}
     */
    public function setSourceAdapter(SourceAdapterInterface $sourceAdapter)
    {
        $this->sourceAdapter = $sourceAdapter;
        return $this;
    }

    /**
     * @see BatchActionBuilderInterface::setHandlerAdapter()
     * {@inheritdoc}
     */
    public function setHandlerAdapter(HandlerAdapterInterface $handlerAdapter)
    {
        $this->handlerAdapter = $handlerAdapter;
        return $this;
    }

    /**
     * @see BatchActionBuilderInterface::build()
     * {@inheritdoc}
     */
    public function build()
    {
        return new BatchAction($this->sourceAdapter, $this->handlerAdapter);
    }
}