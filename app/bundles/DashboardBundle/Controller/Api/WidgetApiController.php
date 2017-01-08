<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class WidgetApiController.
 */
class WidgetApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('dashboard');
        $this->entityClass      = 'Mautic\DashboardBundle\Entity\Widget';
        $this->entityNameOne    = 'widget';
        $this->entityNameMulti  = 'widgets';
        $this->serializerGroups = [];

        parent::initialize($event);
    }

    /**
     * Obtains a list of available widget types.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getTypesAction()
    {
        $dispatcher = $this->dispatcher;
        $event      = new WidgetTypeListEvent();
        $event->setTranslator($this->get('translator'));
        $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE, $event);
        $view = $this->view(['success' => 1, 'types' => $event->getTypes()], Codes::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of available widget types.
     *
     * @param string $type of the widget
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDataAction($type)
    {
        $start      = microtime(true);
        $timezone   = InputHelper::clean($this->request->get('timezone', null));
        $from       = InputHelper::clean($this->request->get('dateFrom', null));
        $to         = InputHelper::clean($this->request->get('dateTo', null));
        $dataFormat = InputHelper::clean($this->request->get('dataFormat', null));
        $response   = ['success' => 0];

        if ($timezone) {
            $fromDate = new \DateTime($from, new \DateTimeZone($timezone));
            $toDate   = new \DateTime($to, new \DateTimeZone($timezone));
        } else {
            $fromDate = new \DateTime($from);
            $toDate   = new \DateTime($to);
        }

        $params = [
            'timeUnit'   => InputHelper::clean($this->request->get('timeUnit', 'Y')),
            'dateFormat' => InputHelper::clean($this->request->get('dateFormat', null)),
            'dateFrom'   => $fromDate,
            'dateTo'     => $toDate,
            'limit'      => InputHelper::int($this->request->get('limit', null)),
            'filter'     => $this->request->get('filter', []),
        ];

        $cacheTimeout = InputHelper::int($this->request->get('cacheTimeout', null));
        $widgetHeight = InputHelper::int($this->request->get('height', 300));

        $widget = new Widget();
        $widget->setParams($params);
        $widget->setType($type);
        $widget->setHeight($widgetHeight);

        if ($cacheTimeout === null) {
            $widget->setCacheTimeout($cacheTimeout);
        }

        $this->model->populateWidgetContent($widget);
        $data = $widget->getTemplateData();

        if (!$data) {
            return $this->notFound();
        }

        if ($dataFormat == 'raw') {
            if (isset($data['chartData']['labels']) && isset($data['chartData']['datasets'])) {
                $rawData = [];
                foreach ($data['chartData']['datasets'] as $dataset) {
                    $rawData[$dataset['label']] = [];
                    foreach ($dataset['data'] as $key => $value) {
                        $rawData[$dataset['label']][$data['chartData']['labels'][$key]] = $value;
                    }
                }
                $data = $rawData;
            } elseif (isset($data['raw'])) {
                $data = $data['raw'];
            }
        } else {
            if (isset($data['raw'])) {
                unset($data['raw']);
            }
        }

        $response['cached']         = $widget->isCached();
        $response['execution_time'] = microtime(true) - $start;
        $response['success']        = 1;
        $response['data']           = $data;

        $view = $this->view($response, Codes::HTTP_OK);

        return $this->handleView($view);
    }
}
