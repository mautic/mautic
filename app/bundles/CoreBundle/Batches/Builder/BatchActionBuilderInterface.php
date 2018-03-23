<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Builder;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Adapter\SourceAdapterInterface;

/**
 * Builder of batch action type. Simple way how to create a batch action.
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