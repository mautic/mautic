<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Model;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\DashboardEvents;
use Symfony\Component\Filesystem\Filesystem;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class DashboardModel.
 */
class DashboardModel extends FormModel
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * DashboardModel constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     * @param PathsHelper          $pathsHelper
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        PathsHelper $pathsHelper,
        Filesystem $filesystem
        )
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->pathsHelper          = $pathsHelper;
        $this->filesystem           = $filesystem;
    }

    /**
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticDashboardBundle:Widget');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'dashboard:widgets';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Widget();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * Load widgets for the current user from database.
     * 
     * @param bool $ignorePaginator
     *
     * @return array
     */
    public function getWidgets($ignorePaginator = false)
    {
        return $this->getEntities([
            'orderBy' => 'w.ordering',
            'filter'  => [
                'force' => [
                    [
                        'column' => 'w.createdBy',
                        'expr'   => 'eq',
                        'value'  => $this->userHelper->getUser()->getId(),
                    ],
                ],
            ],
            'ignore_paginator' => $ignorePaginator,
        ]);
    }

    /**
     * Creates an array that represents the dashboard and all its widgets.
     * Useful for dashboard exports.
     *
     * @param string $name
     * 
     * @return array
     */
    public function toArray($name)
    {
        return [
            'name'        => $name,
            'description' => $this->generateDescription(),
            'widgets'     => array_map(
                function($widget) {
                    return $widget->toArray();
                },
                $this->getWidgets(true)
            ),
        ];
    }

    /**
     * Saves the dashboard snapshot to the user folder
     *
     * @param string $name
     * 
     * @throws IOException
     */
    public function saveSnapshot($name)
    {
        $dir      = $this->pathsHelper->getSystemPath('dashboard.user');
        $filename = InputHelper::filename($name, 'json');
        $path     = $dir.'/'.$filename;
        $this->filesystem->dumpFile($path, json_encode($this->toArray($name)));
    }

    /**
     * Generates a translatable description for a dashboard.
     *
     * @return string
     */
    public function generateDescription()
    {
        return $this->translator->trans(
            'mautic.dashboard.generated_by',
            [
                '%name%' => $this->userHelper->getUser()->getName(),
                '%date%' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Fill widgets with their content.
     *
     * @param array $widgets
     * @param array $filter
     */
    public function populateWidgetsContent(&$widgets, $filter = [])
    {
        if (count($widgets)) {
            foreach ($widgets as &$widget) {
                if (!($widget instanceof Widget)) {
                    $widget = $this->populateWidgetEntity($widget);
                }
                $this->populateWidgetContent($widget, $filter);
            }
        }
    }

    /**
     * Creates a new Widget object from an array data.
     *
     * @param array $data
     *
     * @return Widget
     */
    public function populateWidgetEntity(array $data)
    {
        $entity = new Widget();

        foreach ($data as $property => $value) {
            $method = 'set'.ucfirst($property);
            if (method_exists($entity, $method)) {
                $entity->$method($value);
            }
            unset($data[$property]);
        }

        return $entity;
    }

    /**
     * Load widget content from the onWidgetDetailGenerate event.
     *
     * @param Widget $widget
     * @param array  $filter
     */
    public function populateWidgetContent(Widget &$widget, $filter = [])
    {
        $cacheDir = $this->coreParametersHelper->getParameter('cached_data_dir', $this->pathsHelper->getSystemPath('cache', true));

        if ($widget->getCacheTimeout() == null || $widget->getCacheTimeout() == -1) {
            $widget->setCacheTimeout($this->coreParametersHelper->getParameter('cached_data_timeout'));
        }

        // Merge global filter with widget params
        $widgetParams = $widget->getParams();
        $resultParams = array_merge($widgetParams, $filter);

        // Add the user timezone
        if (empty($resultParams['timezone'])) {
            $resultParams['timezone'] = $this->userHelper->getUser()->getTimezone();
        }

        // Clone the objects in param array to avoid reference issues if some subscriber changes them
        foreach ($resultParams as &$param) {
            if (is_object($param)) {
                $param = clone $param;
            }
        }

        $widget->setParams($resultParams);

        $event = new WidgetDetailEvent($this->translator);
        $event->setWidget($widget);

        $event->setCacheDir($cacheDir, $this->userHelper->getUser()->getId());
        $event->setSecurity($this->security);
        $this->dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_DETAIL_GENERATE, $event);
    }

    /**
     * Clears the temporary widget cache.
     */
    public function clearDashboardCache()
    {
        $cacheDir     = $this->coreParametersHelper->getParameter('cached_data_dir', $this->pathsHelper->getSystemPath('cache', true));
        $cacheStorage = new CacheStorageHelper($cacheDir, $this->userHelper->getUser()->getId());
        $cacheStorage->clear();
    }

    /**
     * {@inheritdoc}
     *
     * @param Widget                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param string|null                         $action
     * @param array                               $options
     *
     * @return \Symfony\Component\Form\Form
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Widget) {
            throw new MethodNotAllowedHttpException(['Widget'], 'Entity must be of class Widget()');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('widget', $entity, $options);
    }

    /**
     * Create/edit entity.
     *
     * @param object $entity
     * @param bool   $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        // Set widget name from widget type if empty
        if (!$entity->getName()) {
            $entity->setName($this->translator->trans('mautic.widget.'.$entity->getType()));
        }

        $entity->setDateModified(new \DateTime);

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Generate default date range filter and time unit.
     *
     * @return array
     */
    public function getDefaultFilter()
    {
        $lastMonth = new \DateTime();
        $lastMonth->sub(new \DateInterval('P30D'));

        $today    = new \DateTime();
        $dateFrom = new \DateTime($this->session->get('mautic.dashboard.date.from', $lastMonth->format('Y-m-d 00:00:00')));
        $dateTo   = new \DateTime($this->session->get('mautic.dashboard.date.to', $today->format('Y-m-d H:i:s')));

        return [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ];
    }
}
