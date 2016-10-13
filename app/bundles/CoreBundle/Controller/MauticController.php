<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Interface MauticController.
 *
 * A dummy interface to ensure that only Mautic bundles are affected by Mautic onKernelController events
 */
interface MauticController
{
    /**
     * Initialize the controller.
     *
     * @param FilterControllerEvent $event
     *
     * @return mixed
     */
    public function initialize(FilterControllerEvent $event);
}
