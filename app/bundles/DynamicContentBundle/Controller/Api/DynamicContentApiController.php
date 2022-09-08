<?php

namespace Mautic\DynamicContentBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;

/**
 * Class DynamicContentApiController.
 */
class DynamicContentApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(\Symfony\Component\HttpKernel\Event\ControllerEvent $event)
    {
        $this->model           = $this->getModel('dynamicContent');
        $this->entityClass     = 'Mautic\DynamicContentBundle\Entity\DynamicContent';
        $this->entityNameOne   = 'dynamicContent';
        $this->entityNameMulti = 'dynamicContents';

        parent::initialize($event);
    }
}
