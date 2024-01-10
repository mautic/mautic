<?php

namespace Mautic\DashboardBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Entity\WidgetRepository;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\Form\Type\WidgetType;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends FormModel<Widget>
 */
class DashboardModel extends FormModel
{
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        private PathsHelper $pathsHelper,
        private Filesystem $filesystem,
        private RequestStack $requestStack,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): WidgetRepository
    {
        return $this->em->getRepository(Widget::class);
    }

    public function getPermissionBase(): string
    {
        return 'dashboard:widgets';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Widget
    {
        if (null === $id) {
            return new Widget();
        }

        return parent::getEntity($id);
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
     */
    public function toArray($name): array
    {
        return [
            'name'        => $name,
            'description' => $this->generateDescription(),
            'widgets'     => array_map(
                fn ($widget) => $widget->toArray(),
                $this->getWidgets(true)
            ),
        ];
    }

    /**
     * Saves the dashboard snapshot to the user folder.
     *
     * @param string $name
     *
     * @throws IOException
     */
    public function saveSnapshot($name): void
    {
        $dir      = $this->pathsHelper->getSystemPath('dashboard.user');
        $filename = InputHelper::filename($name, 'json');
        $path     = $dir.'/'.$filename;
        $this->filesystem->dumpFile($path, json_encode($this->toArray($name)));
    }

    /**
     * Generates a translatable description for a dashboard.
     */
    public function generateDescription(): string
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
    public function populateWidgetsContent(&$widgets, $filter = []): void
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
     */
    public function populateWidgetEntity(array $data): Widget
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
     * @param array $filter
     */
    public function populateWidgetContent(Widget $widget, $filter = []): void
    {
        $cacheDir = $this->coreParametersHelper->get('cached_data_dir', $this->pathsHelper->getSystemPath('cache', true));

        if (null === $widget->getCacheTimeout() || -1 === $widget->getCacheTimeout()) {
            $widget->setCacheTimeout($this->coreParametersHelper->get('cached_data_timeout'));
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
        $this->dispatcher->dispatch($event, DashboardEvents::DASHBOARD_ON_MODULE_DETAIL_GENERATE);
    }

    /**
     * Clears the temporary widget cache.
     */
    public function clearDashboardCache(): void
    {
        $cacheDir     = $this->coreParametersHelper->get('cached_data_dir', $this->pathsHelper->getSystemPath('cache', true));
        $cacheStorage = new CacheStorageHelper(CacheStorageHelper::ADAPTOR_FILESYSTEM, $this->userHelper->getUser()->getId(), null, $cacheDir);

        $cacheStorage->clear();
    }

    /**
     * @param Widget      $entity
     * @param string|null $action
     * @param array       $options
     *
     * @return \Symfony\Component\Form\FormInterface<mixed>
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Widget) {
            throw new MethodNotAllowedHttpException(['Widget'], 'Entity must be of class Widget()');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(WidgetType::class, $entity, $options);
    }

    /**
     * Create/edit entity.
     *
     * @param object $entity
     * @param bool   $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        // Set widget name from widget type if empty
        if (!$entity->getName()) {
            $entity->setName($this->translator->trans('mautic.widget.'.$entity->getType()));
        }

        $entity->setDateModified(new \DateTime());

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Generate default date range filter and time unit.
     */
    public function getDefaultFilter(): array
    {
        $dateRangeDefault = $this->coreParametersHelper->get('default_daterange_filter', '-1 month');
        $dateRangeStart   = new \DateTime();
        $dateRangeStart->modify($dateRangeDefault);

        $session  = $this->requestStack->getSession();
        $today    = new \DateTime();
        $dateFrom = new \DateTime($session->get('mautic.daterange.form.from', $dateRangeStart->format('Y-m-d 00:00:00')));
        $dateTo   = new \DateTime($session->get('mautic.daterange.form.to', $today->format('Y-m-d 23:59:59')));

        return [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo->modify('23:59:59'), // till end of the 'to' date selected
        ];
    }
}
