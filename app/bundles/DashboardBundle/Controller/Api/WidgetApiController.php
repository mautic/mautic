<?php

namespace Mautic\DashboardBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;
use Mautic\DashboardBundle\Model\DashboardModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Widget>
 */
class WidgetApiController extends CommonApiController
{
    /**
     * @var DashboardModel|null
     */
    protected $model;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper, MauticFactory $factory)
    {
        $dashboardModel = $modelFactory->getModel('dashboard');
        \assert($dashboardModel instanceof DashboardModel);

        $this->model            = $dashboardModel;
        $this->entityClass      = Widget::class;
        $this->entityNameOne    = 'widget';
        $this->entityNameMulti  = 'widgets';
        $this->serializerGroups = [];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper, $factory);
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
        $event->setTranslator($this->translator);
        $dispatcher->dispatch($event, DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE);
        $view = $this->view(['success' => 1, 'types' => $event->getTypes()], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of available widget types.
     *
     * @param string $type of the widget
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDataAction(Request $request, $type)
    {
        $start      = microtime(true);
        $timezone   = InputHelper::clean($request->get('timezone', null));
        $from       = InputHelper::clean($request->get('dateFrom', null));
        $to         = InputHelper::clean($request->get('dateTo', null));
        $dataFormat = InputHelper::clean($request->get('dataFormat', null));
        $unit       = InputHelper::clean($request->get('timeUnit', 'Y'));
        $dataset    = InputHelper::clean($request->get('dataset', []));
        $response   = ['success' => 0];

        try {
            DateTimeHelper::validateMysqlDateTimeUnit($unit);
        } catch (\InvalidArgumentException $e) {
            return $this->returnError($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($timezone) {
            $fromDate = new \DateTime($from, new \DateTimeZone($timezone));
            $toDate   = new \DateTime($to, new \DateTimeZone($timezone));
        } else {
            $fromDate = new \DateTime($from);
            $toDate   = new \DateTime($to);
        }

        $params = [
            'timeUnit'   => InputHelper::clean($request->get('timeUnit', 'Y')),
            'dateFormat' => InputHelper::clean($request->get('dateFormat', null)),
            'dateFrom'   => $fromDate,
            'dateTo'     => $toDate,
            'limit'      => (int) $request->get('limit', null),
            'filter'     => InputHelper::clean($request->get('filter', [])),
            'dataset'    => $dataset,
        ];

        // Merge filters into the root array as well as that's how widget edit forms send them.
        $params = array_merge($params, $params['filter']);

        $cacheTimeout = (int) $request->get('cacheTimeout', 0);
        $widgetHeight = (int) $request->get('height', 300);

        $widget = new Widget();
        $widget->setParams($params);
        $widget->setType($type);
        $widget->setHeight($widgetHeight);
        $widget->setCacheTimeout($cacheTimeout);

        $this->model->populateWidgetContent($widget);
        $data = $widget->getTemplateData();

        if (!$data) {
            return $this->notFound();
        }

        if ('raw' == $dataFormat) {
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

        $view = $this->view($response, Response::HTTP_OK);

        return $this->handleView($view);
    }
}
