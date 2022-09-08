<?php

namespace Mautic\CoreBundle\Controller;

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
     * @return mixed
     */
    public function initialize(\Symfony\Component\HttpKernel\Event\ControllerEvent $event);
}
