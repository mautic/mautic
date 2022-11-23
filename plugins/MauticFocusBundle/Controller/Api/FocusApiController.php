<?php

namespace MauticPlugin\MauticFocusBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Focus>
 */
class FocusApiController extends CommonApiController
{
    /**
     * @var FocusModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        parent::initialize($event);

        $focusModel = $this->getModel('focus');

        if (!$focusModel instanceof FocusModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        $this->model           = $focusModel;
        $this->entityClass     = Focus::class;
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
