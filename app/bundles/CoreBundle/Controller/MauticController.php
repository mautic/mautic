<?php

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * A dummy interface to ensure that only Mautic bundles are affected by Mautic onKernelController events.
 */
interface MauticController
{
    /**
     * @return mixed
     */
    public function initialize(ControllerEvent $event);
}
