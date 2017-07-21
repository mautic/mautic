<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
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
        $this->permissionBase  = 'plugin:focus:items';
        $this->dataInputMasks  = [
            'html'   => 'html',
            'editor' => 'html',
        ];
    }

    public function generateJsAction($id)
    {
        $focus = $this->model->getEntity($id);
        $view  = $this->view(['js' => $this->model->generateJavascript($focus)], Codes::HTTP_OK);

        return $this->handleView($view);
    }
}
