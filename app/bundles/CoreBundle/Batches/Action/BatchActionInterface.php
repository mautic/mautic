<?php

namespace Mautic\CoreBundle\Batches\Action;

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Adapter\SourceAdapterInterface;

/**
 * Batch action type with all stuffs needed to be executed.
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