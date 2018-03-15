<?php

namespace Mautic\CoreBundle\Batches\Action;

use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Adapter\SourceAdapterInterface;

/**
 * Batch action type with all stuffs needed to be executed.
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
interface BatchActionInterface
{
    /**
     * Get source adapter.
     *
     * @return SourceAdapterInterface
     */
    public function getSourceAdapter();

    /**
     * Get handler.
     *
     * @return HandlerAdapterInterface
     */
    public function getHandlerAdapter();
}