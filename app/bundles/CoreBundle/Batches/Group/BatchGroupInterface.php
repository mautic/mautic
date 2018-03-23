<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Group;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;

/**
 * Group of batch actions. With this interface you are able to define different batches for each controller/action.
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