<?php

namespace Mautic\CoreBundle\Batches\Group;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;

/**
 * Group of batch actions. With this interface you are able to define different batches for each controller/action.
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
interface BatchGroupInterface
{
    /**
     * Return batch actions for this group.
     *
     * @return BatchActionInterface[]
     */
    public function registerActions();
}