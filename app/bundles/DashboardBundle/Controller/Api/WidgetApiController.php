<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\Entity\Widget;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class WidgetApiController
 *
 * @package Mautic\DashboardBundle\Controller\Api
 */
class WidgetApiController extends CommonApiController
{

    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->factory->getModel('dashboard');
        $this->entityClass      = 'Mautic\DashboardBundle\Entity\Widget';
        $this->entityNameOne    = 'widget';
        $this->entityNameMulti  = 'widgets';
        $this->permissionBase   = 'dashboard:widgets';
        $this->serializerGroups = array();
    }

    /**
     * Obtains a list of available widget types
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getTypesAction()
    {
        $dispatcher = $this->factory->getDispatcher();
        $event      = new WidgetTypeListEvent();
        $event->setTranslator($this->get('translator'));
        $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE, $event);
        $view = $this->view(array('success' => 1, 'types' => $event->getTypes()), Codes::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of available widget types
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDataAction($type)
    {
        $params = array(
            'amount' => $this->request->get('amount', 12),
            'timeUnit' => $this->request->get('timeUnit', 'Y')
        );

        $widget = new Widget;
        $widget->setParams($params);
        $widget->setType($type);
        $this->model->populateWidgetContent($widget);
        $data = $widget->getTemplateData();

        if (!$data) {
            return $this->notFound();
        }

        $view = $this->view(array('success' => 1, 'data' => $data), Codes::HTTP_OK);

        return $this->handleView($view);
    }
}
