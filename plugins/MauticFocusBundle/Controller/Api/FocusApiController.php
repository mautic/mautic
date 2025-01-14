<?php

namespace MauticPlugin\MauticFocusBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class FocusApiController.
 */
class FocusApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);

        $this->model           = $this->getModel('focus');
        $this->entityClass     = 'MauticPlugin\MauticFocusBundle\Entity\Focus';
        $this->entityNameOne   = 'focus';
        $this->entityNameMulti = 'focus';
        $this->permissionBase  = 'focus:items';
        $this->dataInputMasks  = [
            'html'   => 'html',
            'editor' => 'html',
        ];
    }

    public function generateJsAction($id)
    {
        $focus = $this->model->getEntity($id);
        $view  = $this->view(['js' => $this->model->generateJavascript($focus)], Response::HTTP_OK);

        return $this->handleView($view);
    }
}
