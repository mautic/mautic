<?php

namespace Mautic\DashboardBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\PhpVersionHelper;
use Mautic\CoreBundle\Release\ThisRelease;
use Mautic\DashboardBundle\Dashboard\Widget as WidgetService;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Form\Type\UploadType;
use Mautic\DashboardBundle\Model\DashboardModel;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DashboardController extends AbstractFormController
{
    /**
     * Generates the default view.
     */
    public function indexAction(Request $request, WidgetService $widget, FormFactoryInterface $formFactory, PathsHelper $pathsHelper): Response
    {
        $model   = $this->getModel('dashboard');
        \assert($model instanceof DashboardModel);
        $widgets = $model->getWidgets();

        // Apply the default dashboard if no widget exists
        if (!count($widgets) && $this->user->getId()) {
            return $this->applyDashboardFileAction($request, $pathsHelper, 'global.default');
        }

        $action          = $this->generateUrl('mautic_dashboard_index');
        $dateRangeFilter = $request->get('daterange', []);

        // Set new date range to the session
        if ($request->isMethod(Request::METHOD_POST)) {
            if (!empty($dateRangeFilter['date_from'])) {
                $from = new \DateTime($dateRangeFilter['date_from']);
                $request->getSession()->set('mautic.daterange.form.from', $from->format(WidgetService::FORMAT_MYSQL));
            }

            if (!empty($dateRangeFilter['date_to'])) {
                $to = new \DateTime($dateRangeFilter['date_to']);
                $request->getSession()->set('mautic.daterange.form.to', $to->format(WidgetService::FORMAT_MYSQL.' 23:59:59'));
            }

            $model->clearDashboardCache();
        }

        // Set new date range to the session, if present in POST
        $widget->setFilter($request);

        // Load date range from session
        $filter = $model->getDefaultFilter();

        // Set the final date range to the form
        $dateRangeFilter['date_from'] = $filter['dateFrom']->format(WidgetService::FORMAT_HUMAN);
        $dateRangeFilter['date_to']   = $filter['dateTo']->format(WidgetService::FORMAT_HUMAN);
        $dateRangeForm                = $formFactory->create(DateRangeType::class, $dateRangeFilter, ['action' => $action]);

        $model->populateWidgetsContent($widgets, $filter);
        $releaseMetadata = ThisRelease::getMetadata();

        return $this->delegateView([
            'viewParameters' => [
                'security'      => $this->security,
                'widgets'       => $widgets,
                'dateRangeForm' => $dateRangeForm->createView(),
                'phpVersion'    => [
                    'isOutdated' => version_compare(PHP_VERSION, $releaseMetadata->getShowPHPVersionWarningIfUnder(), 'lt'),
                    'version'    => PhpVersionHelper::getCurrentSemver(),
                ],
            ],
            'contentTemplate' => '@MauticDashboard/Dashboard/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dashboard_index',
                'mauticContent' => 'dashboard',
                'route'         => $this->generateUrl('mautic_dashboard_index'),
            ],
        ]);
    }

    public function widgetAction(Request $request, WidgetService $widgetService, $widgetId): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException('Not found.');
        }

        $widgetService->setFilter($request);
        $widget        = $widgetService->get((int) $widgetId);

        if (!$widget) {
            throw new NotFoundHttpException('Not found.');
        }

        $content = $this->get('twig')->render(
            '@MauticDashboard/Dashboard/widget.html.twig',
            ['widget' => $widget]
        );

        return new JsonResponse([
            'success'      => 1,
            'widgetId'     => $widgetId,
            'widgetHtml'   => $content,
            'widgetWidth'  => $widget->getWidth(),
            'widgetHeight' => $widget->getHeight(),
        ]);
    }

    /**
     * Generate new dashboard widget and processes post data.
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function newAction(Request $request, FormFactoryInterface $formFactory)
    {
        // retrieve the entity
        $widget = new Widget();

        $model  = $this->getModel('dashboard');
        \assert($model instanceof DashboardModel);
        $action = $this->generateUrl('mautic_dashboard_action', ['objectAction' => 'new']);

        // get the user form factory
        $form       = $model->createForm($widget, $formFactory, $action);
        $closeModal = false;
        $valid      = false;

        // /Check for a submitted form and process it
        if ($request->isMethod(Request::METHOD_POST)) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $closeModal = true;

                    // form is valid so process the data
                    $model->saveEntity($widget);
                }
            } else {
                $closeModal = true;
            }
        }

        if ($closeModal) {
            // just close the modal
            $passthroughVars = [
                'closeModal'    => 1,
                'mauticContent' => 'widget',
            ];

            $filter = $model->getDefaultFilter();
            $model->populateWidgetContent($widget, $filter);

            if ($valid && !$cancelled) {
                $passthroughVars['upWidgetCount'] = 1;
                $passthroughVars['widgetHtml']    = $this->renderView('@MauticDashboard/Widget/detail.html.twig', [
                    'widget' => $widget,
                ]);
                $passthroughVars['widgetId']     = $widget->getId();
                $passthroughVars['widgetWidth']  = $widget->getWidth();
                $passthroughVars['widgetHeight'] = $widget->getHeight();
            }

            return new JsonResponse($passthroughVars);
        } else {
            return $this->delegateView([
                'viewParameters' => [
                    'form' => $form->createView(),
                ],
                'contentTemplate' => '@MauticDashboard/Widget/form.html.twig',
            ]);
        }
    }

    /**
     * edit widget and processes post data.
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function editAction(Request $request, FormFactoryInterface $formFactory, $objectId)
    {
        $model  = $this->getModel('dashboard');
        \assert($model instanceof DashboardModel);
        $widget = $model->getEntity($objectId);
        $action = $this->generateUrl('mautic_dashboard_action', ['objectAction' => 'edit', 'objectId' => $objectId]);

        // get the user form factory
        $form       = $model->createForm($widget, $formFactory, $action);
        $closeModal = false;
        $valid      = false;
        // /Check for a submitted form and process it
        if ($request->isMethod(Request::METHOD_POST)) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $closeModal = true;

                    // form is valid so process the data
                    $model->saveEntity($widget);
                }
            } else {
                $closeModal = true;
            }
        }

        if ($closeModal) {
            // just close the modal
            $passthroughVars = [
                'closeModal'    => 1,
                'mauticContent' => 'widget',
            ];

            $filter = $model->getDefaultFilter();
            $model->populateWidgetContent($widget, $filter);

            if ($valid && !$cancelled) {
                $passthroughVars['upWidgetCount'] = 1;
                $passthroughVars['widgetHtml']    = $this->renderView('@MauticDashboard/Widget/detail.html.twig', [
                    'widget' => $widget,
                ]);
                $passthroughVars['widgetId']     = $widget->getId();
                $passthroughVars['widgetWidth']  = $widget->getWidth();
                $passthroughVars['widgetHeight'] = $widget->getHeight();
            }

            return new JsonResponse($passthroughVars);
        } else {
            return $this->delegateView([
                'viewParameters' => [
                    'form' => $form->createView(),
                ],
                'contentTemplate' => '@MauticDashboard/Widget/form.html.twig',
            ]);
        }
    }

    /**
     * Deletes entity if exists.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $flashes = [];
        $success = 0;

        /** @var DashboardModel $model */
        $model  = $this->getModel('dashboard');
        $entity = $model->getEntity($objectId);

        if ($entity) {
            $model->deleteEntity($entity);
            $name      = $entity->getName();
            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $name,
                    '%id%'   => $objectId,
                ],
            ];
            $success = 1;
        } else {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.api.client.error.notfound',
                'msgVars' => ['%id%' => $objectId],
            ];
        }

        return $this->postActionRedirect(
            [
                'success' => $success,
                'flashes' => $flashes,
            ]
        );
    }

    /**
     * Saves the widgets of current user into a json and stores it for later as a file.
     *
     * @return Response
     */
    public function saveAction(Request $request)
    {
        // Accept only AJAX POST requests because those are check for CSRF tokens
        if (!$request->isMethod(Request::METHOD_POST) || !$request->isXmlHttpRequest()) {
            return $this->accessDenied();
        }

        $name = $this->getNameFromRequest($request);

        /** @var DashboardModel $dashboardModel */
        $dashboardModel = $this->getModel('dashboard');
        try {
            $dashboardModel->saveSnapshot($name);
            $type = 'notice';
            $msg  = $this->translator->trans('mautic.dashboard.notice.save', [
                '%name%'    => $name,
                '%viewUrl%' => $this->generateUrl(
                    'mautic_dashboard_action',
                    [
                        'objectAction' => 'import',
                    ]
                ),
            ], 'flashes');
        } catch (IOException $e) {
            $type = 'error';
            $msg  = $this->translator->trans('mautic.dashboard.error.save', [
                '%msg%' => $e->getMessage(),
            ], 'flashes');
        }

        return $this->postActionRedirect(
            [
                'flashes' => [
                    [
                        'type' => $type,
                        'msg'  => $msg,
                    ],
                ],
            ]
        );
    }

    /**
     * Exports the widgets of current user into a json file and downloads it.
     */
    public function exportAction(Request $request): JsonResponse
    {
        $dashboardModel = $this->getModel('dashboard');
        \assert($dashboardModel instanceof DashboardModel);
        $filename = InputHelper::filename($this->getNameFromRequest($request), 'json');
        $response = new JsonResponse($dashboardModel->toArray($filename));
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    /**
     * Exports the widgets of current user into a json file.
     */
    public function deleteDashboardFileAction(Request $request, PathsHelper $pathsHelper): RedirectResponse
    {
        $file = $request->get('file');

        $parts = explode('.', $file);
        $type  = array_shift($parts);
        $name  = implode('.', $parts);

        $dir  = $pathsHelper->getSystemPath("dashboard.$type");
        $path = $dir.'/'.$name.'.json';

        if (file_exists($path) && is_writable($path)) {
            unlink($path);
        }

        return $this->redirectToRoute('mautic_dashboard_action', ['objectAction' => 'import']);
    }

    /**
     * Applies dashboard layout.
     *
     * @param string|null $file
     */
    public function applyDashboardFileAction(Request $request, PathsHelper $pathsHelper, $file = null): RedirectResponse
    {
        if (!$file) {
            $file = $request->get('file');
        }

        $parts = explode('.', $file);
        $type  = array_shift($parts);
        $name  = implode('.', $parts);

        $dir  = $pathsHelper->getSystemPath("dashboard.$type");
        $path = $dir.'/'.$name.'.json';

        if (!file_exists($path) || !is_readable($path)) {
            $this->addFlashMessage('mautic.dashboard.upload.filenotfound', [], 'error', 'validators');

            return $this->redirectToRoute('mautic_dashboard_action', ['objectAction' => 'import']);
        }

        $widgets = json_decode(file_get_contents($path), true);
        if (isset($widgets['widgets'])) {
            $widgets = $widgets['widgets'];
        }

        if ($widgets) {
            /** @var DashboardModel $model */
            $model = $this->getModel('dashboard');

            $model->clearDashboardCache();

            $currentWidgets = $model->getWidgets();

            if (count($currentWidgets)) {
                foreach ($currentWidgets as $widget) {
                    $model->deleteEntity($widget);
                }
            }

            $filter = $model->getDefaultFilter();
            foreach ($widgets as $widget) {
                $widget = $model->populateWidgetEntity($widget);
                $model->saveEntity($widget);
            }
        }

        return $this->redirect($this->get('router')->generate('mautic_dashboard_index'));
    }

    public function importAction(Request $request, FormFactoryInterface $formFactory, PathsHelper $pathsHelper): Response
    {
        $preview = $request->get('preview');

        /** @var DashboardModel $model */
        $model = $this->getModel('dashboard');

        $directories = [
            'user'   => $pathsHelper->getSystemPath('dashboard.user'),
            'global' => $pathsHelper->getSystemPath('dashboard.global'),
        ];

        $action = $this->generateUrl('mautic_dashboard_action', ['objectAction' => 'import']);
        $form   = $formFactory->create(UploadType::class, [], ['action' => $action]);

        if ($request->isMethod(Request::METHOD_POST)) {
            if (!$this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $fileData = $form['file']->getData();
                    if (!empty($fileData)) {
                        $extension = pathinfo($fileData->getClientOriginalName(), PATHINFO_EXTENSION);
                        if ('json' === $extension) {
                            $fileData->move($directories['user'], $fileData->getClientOriginalName());
                        } else {
                            $form->addError(
                                new FormError(
                                    $this->translator->trans('mautic.core.not.allowed.file.extension', ['%extension%' => $extension], 'validators')
                                )
                            );
                        }
                    } else {
                        $form->addError(
                            new FormError(
                                $this->translator->trans('mautic.dashboard.upload.filenotfound', [], 'validators')
                            )
                        );
                    }
                }
            }
        }

        $dashboardFiles = ['user' => [], 'gobal' => []];
        $dashboards     = [];

        if (is_readable($directories['user'])) {
            // User specific layouts
            chdir($directories['user']);
            $dashboardFiles['user'] = glob('*.json');
        }

        if (is_readable($directories['global'])) {
            // Global dashboards
            chdir($directories['global']);
            $dashboardFiles['global'] = glob('*.json');
        }

        foreach ($dashboardFiles as $type => $dirDashboardFiles) {
            $tempDashboard = [];
            foreach ($dirDashboardFiles as $dashId => $dashboard) {
                $dashboard = str_replace('.json', '', $dashboard);
                $config    = json_decode(
                    file_get_contents($directories[$type].'/'.$dirDashboardFiles[$dashId]),
                    true
                );

                // Check for name, description, etc
                $tempDashboard[$dashboard] = [
                    'type'        => $type,
                    'name'        => $config['name'] ?? $dashboard,
                    'description' => $config['description'] ?? '',
                    'widgets'     => $config['widgets'] ?? $config,
                ];
            }

            // Sort by name
            uasort($tempDashboard,
                fn ($a, $b): int => strnatcasecmp($a['name'], $b['name'])
            );

            $dashboards = array_merge(
                $dashboards,
                $tempDashboard
            );
        }

        if ($preview && isset($dashboards[$preview])) {
            // @todo check is_writable
            $widgets = $dashboards[$preview]['widgets'];
            $filter  = $model->getDefaultFilter();
            $model->populateWidgetsContent($widgets, $filter);
        } else {
            $widgets = [];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'       => $form->createView(),
                    'dashboards' => $dashboards,
                    'widgets'    => $widgets,
                    'preview'    => $preview,
                ],
                'contentTemplate' => '@MauticDashboard/Dashboard/import.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_dashboard_index',
                    'mauticContent' => 'dashboardImport',
                    'route'         => $this->generateUrl(
                        'mautic_dashboard_action',
                        [
                            'objectAction' => 'import',
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Gets name from request and defaults it to the timestamp if not provided.
     *
     * @return string
     */
    private function getNameFromRequest(Request $request)
    {
        return $request->get('name', (new \DateTime())->format('Y-m-dTH:i:s'));
    }
}
