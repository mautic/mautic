<?php

namespace Mautic\DynamicContentBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<DynamicContent>
 */
class DynamicContentApiController extends CommonApiController
{
    public function initialize(ControllerEvent $event)
    {
        $dynamicContentModel = $this->getModel('dynamicContent');
        \assert($dynamicContentModel instanceof DynamicContentModel);

        $this->model           = $dynamicContentModel;
        $this->entityClass     = DynamicContent::class;
        $this->entityNameOne   = 'dynamicContent';
        $this->entityNameMulti = 'dynamicContents';

        parent::initialize($event);
    }
}
