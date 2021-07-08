<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PhpVersionHelper;
use Mautic\CoreBundle\Release\ThisRelease;
use Mautic\DashboardBundle\Dashboard\Widget as WidgetService;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Form\Type\UploadType;
use Mautic\DashboardBundle\Model\DashboardModel;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormError;
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
     *
     * @return JsonResponse|Response
     */
    public function indexAction()
    {
        /** @var DashboardModel $model */
        $model   = $this->getModel('dashboard');
        $widgets = $model->getWidgets();

        // Apply the default dashboard if no widget exists
        if (!count($widgets) && $this->user->getId()) {
            return $this->applyDashboardFileAction('global.default');
        }

        $action          = $this->generateUrl('mautic_dashboard_index');
        $dateRangeFilter = $this->request->get('daterange', []);

        // Set new date range to the session
        if ($this->request->isMethod(Request::METHOD_POST)) {
            $session = $this->get('session');
            if (!empty($dateRangeFilter['date_from'])) {
                $from = new \DateTime($dateRangeFilter['date_from']);
                $session->set('mautic.daterange.form.from', $from->format(WidgetService::FORMAT_MYSQL));
            }

            if (!empty($dateRangeFilter['date_to'])) {
                $to = new \DateTime($dateRangeFilter['date_to']);
                $session->set('mautic.daterange.form.to', $to->format(WidgetService::FORMAT_MYSQL.' 23:59:59'));
            }

            $model->clearDashboardCache();
        }

        // Set new date range to the session, if present in POST
        $this->get('mautic.dashboard.widget')->setFilter($this->request);

        // Load date range from session
        $filter = $model->getDefaultFilter();

        // Set the final date range to the form
        $dateRangeFilter['date_from'] = $filter['dateFrom']->format(WidgetService::FORMAT_HUMAN);
        $dateRangeFilter['date_to']   = $filter['dateTo']->format(WidgetService::FORMAT_HUMAN);
        $dateRangeForm                = $this->get('form.factory')->create(DateRangeType::class, $dateRangeFilter, ['action' => $action]);

        $model->populateWidgetsContent($widgets, $filter);
        $releaseMetadata = ThisRelease::getMetadata();

        return $this->delegateView([
            'viewParameters' => [
                'security'      => $this->get('mautic.security'),
                'widgets'       => $widgets,
                'dateRangeForm' => $dateRangeForm->createView(),
                'phpVersion'    => [
                    'isOutdated' => version_compare(PHP_VERSION, $releaseMetadata->getShowPHPVersionWarningIfUnder(), 'lt'),
                    'version'    => PhpVersionHelper::getCurrentSemver(),
                ],
            ],
            'contentTemplate' => 'MauticDashboardBundle:Dashboard:index.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dashboard_index',
                'mauticContent' => 'dashboard',
                'route'         => $this->generateUrl('mautic_dashboard_index'),
            ],
        ]);
    }

    /**
     * @return JsonResponse|Response
     */
    public function widgetAction($widgetId)
    {
        if (!$this->request->isXmlHttpRequest()) {
            throw new NotFoundHttpException('Not found.');
        }

        /** @var WidgetService $widgetService */
        $widgetService = $this->get('mautic.dashboard.widget');
        $widgetService->setFilter($this->request);
        $widget        = $widgetService->get((int) $widgetId);

        if (!$widget) {
            throw new NotFoundHttpException('Not found.');
        }

        $response = $this->render(
            'MauticDashboardBundle:Dashboard:widget.html.php',
            ['widget' => $widget]
        );

        return new JsonResponse([
            'success'      => 1,
            'widgetId'     => $widgetId,
            'widgetHtml'   => $response->getContent(),
            'widgetWidth'  => $widget->getWidth(),
            'widgetHeight' => $widget->getHeight(),
        ]);
    }

    /**
     * Generate new dashboard widget and processes post data.
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function newAction()
    {
        //retrieve the entity
        $widget = new Widget();

        $model  = $this->getModel('dashboard');
        $action = $this->generateUrl('mautic_dashboard_action', ['objectAction' => 'new']);

        //get the user form factory
        $form       = $model->createForm($widget, $this->get('form.factory'), $action);
        $closeModal = false;
        $valid      = false;

        ///Check for a submitted form and process it
        if ($this->request->isMethod(Request::METHOD_POST)) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $closeModal = true;

                    //form is valid so process the data
                    $model->saveEntity($widget);
                }
            } else {
                $closeModal = true;
            }
        }

        if ($closeModal) {
            //just close the modal
            $passthroughVars = [
                'closeModal'    => 1,
                'mauticContent' => 'widget',
            ];

            $filter = $model->getDefaultFilter();
            $model->populateWidgetContent($widget, $filter);

            if ($valid && !$cancelled) {
                $passthroughVars['upWidgetCount'] = 1;
                $passthroughVars['widgetHtml']    = $this->renderView('MauticDashboardBundle:Widget:detail.html.php', [
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
                'contentTemplate' => 'MauticDashboardBundle:Widget:form.html.php',
            ]);
        }
    }

    /**
     * edit widget and processes post data.
     *
     * @param $objectId
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function editAction($objectId)
    {
        $model  = $this->getModel('dashboard');
        $widget = $model->getEntity($objectId);
        $action = $this->generateUrl('mautic_dashboard_action', ['objectAction' => 'edit', 'objectId' => $objectId]);

        //get the user form factory
        $form       = $model->createForm($widget, $this->get('form.factory'), $action);
        $closeModal = false;
        $valid      = false;
        ///Check for a submitted form and process it
        if ($this->request->isMethod(Request::METHOD_POST)) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $closeModal = true;

                    //form is valid so process the data
                    $model->saveEntity($widget);
                }
            } else {
                $closeModal = true;
            }
        }

        if ($closeModal) {
            //just close the modal
            $passthroughVars = [
                'closeModal'    => 1,
                'mauticContent' => 'widget',
            ];

            $filter = $model->getDefaultFilter();
            $model->populateWidgetContent($widget, $filter);

            if ($valid && !$cancelled) {
                $passthroughVars['upWidgetCount'] = 1;
                $passthroughVars['widgetHtml']    = $this->renderView('MauticDashboardBundle:Widget:detail.html.php', [
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
                'contentTemplate' => 'MauticDashboardBundle:Widget:form.html.php',
            ]);
        }
    }

    /**
     * Deletes entity if exists.
     *
     * @param int $objectId
     *
     * @return JsonResponse|RedirectResponse
     */
    public function deleteAction($objectId)
    {
        /** @var Request $request */
        $request = $this->get('request_stack')->getCurrentRequest();

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
     * @return JsonResponse
     */
    public function saveAction()
    {
        // Accept only AJAX POST requests because those are check for CSRF tokens
        if (!$this->request->isMethod(Request::METHOD_POST) || !$this->request->isXmlHttpRequest()) {
            return $this->accessDenied();
        }

        $name = $this->getNameFromRequest();

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
     *
     * @return JsonResponse
     */
    public function exportAction()
    {
        $filename = InputHelper::filename($this->getNameFromRequest(), 'json');
        $response = new JsonResponse($this->getModel('dashboard')->toArray($filename));
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Expires', 0);
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    /**
     * Exports the widgets of current user into a json file.
     *
     * @return JsonResponse|Response
     */
    public function deleteDashboardFileAction()
    {
        $file = $this->request->get('file');

        $parts = explode('.', $file);
        $type  = array_shift($parts);
        $name  = implode('.', $parts);

        $dir  = $this->container->get('mautic.helper.paths')->getSystemPath("dashboard.$type");
        $path = $dir.'/'.$name.'.json';

        if (file_exists($path) && is_writable($path)) {
            unlink($path);
        }

        return $this->redirect($this->generateUrl('mautic_dashboard_action', ['objectAction' => 'import']));
    }

    /**
     * Applies dashboard layout.
     *
     * @param null $file
     *
     * @return JsonResponse|Response
     */
    public function applyDashboardFileAction($file = null)
    {
        if (!$file) {
            $file = $this->request->get('file');
        }

        $parts = explode('.', $file);
        $type  = array_shift($parts);
        $name  = implode('.', $parts);

        $dir  = $this->container->get('mautic.helper.paths')->getSystemPath("dashboard.$type");
        $path = $dir.'/'.$name.'.json';

        if (!file_exists($path) || !is_readable($path)) {
            $this->addFlash('mautic.dashboard.upload.filenotfound', [], 'error', 'validators');

            return $this->redirect($this->generateUrl('mautic_dashboard_action', ['objectAction' => 'import']));
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
                $widget = $model->populateWidgetEntity($widget, $filter);
                $model->saveEntity($widget);
            }
        }

        return $this->redirect($this->get('router')->generate('mautic_dashboard_index'));
    }

    /**
     * @return JsonResponse|Response
     */
    public function importAction()
    {
        $preview = $this->request->get('preview');

        /** @var DashboardModel $model */
        $model = $this->getModel('dashboard');

        $directories = [
            'user'   => $this->container->get('mautic.helper.paths')->getSystemPath('dashboard.user'),
            'global' => $this->container->get('mautic.helper.paths')->getSystemPath('dashboard.global'),
        ];

        $action = $this->generateUrl('mautic_dashboard_action', ['objectAction' => 'import']);
        $form   = $this->get('form.factory')->create(UploadType::class, [], ['action' => $action]);

        if ($this->request->isMethod(Request::METHOD_POST)) {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
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
                    'name'        => (isset($config['name'])) ? $config['name'] : $dashboard,
                    'description' => (isset($config['description'])) ? $config['description'] : '',
                    'widgets'     => (isset($config['widgets'])) ? $config['widgets'] : $config,
                ];
            }

            // Sort by name
            uasort($tempDashboard,
                function ($a, $b) {
                    return strnatcasecmp($a['name'], $b['name']);
                }
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
                'contentTemplate' => 'MauticDashboardBundle:Dashboard:import.html.php',
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
    private function getNameFromRequest()
    {
        return $this->request->get('name', (new \DateTime())->format('Y-m-dTH:i:s'));
    }
}
