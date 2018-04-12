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

use Symfony\Component\Form\FormError;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class DashboardController.
 */
class DashboardController extends FormController
{
    /**
     * Generates the default view.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
        $model   = $this->getModel('dashboard');
        $widgets = $model->getWidgets();

        // Apply the default dashboard if no widget exists
        if (!count($widgets) && $this->user->getId()) {
            return $this->applyDashboardFileAction('global.default');
        }

        $humanFormat     = 'M j, Y';
        $mysqlFormat     = 'Y-m-d';
        $action          = $this->generateUrl('mautic_dashboard_index');
        $dateRangeFilter = $this->request->get('daterange', []);

        // Set new date range to the session
        if ($this->request->isMethod('POST')) {
            $session = $this->get('session');
            if (!empty($dateRangeFilter['date_from'])) {
                $from = new \DateTime($dateRangeFilter['date_from']);
                $session->set('mautic.dashboard.date.from', $from->format($mysqlFormat));
            }

            if (!empty($dateRangeFilter['date_to'])) {
                $to = new \DateTime($dateRangeFilter['date_to']);
                $session->set('mautic.dashboard.date.to', $to->format($mysqlFormat));
            }

            $model->clearDashboardCache();
        }

        // Load date range from session
        $filter = $model->getDefaultFilter();

        // Set the final date range to the form
        $dateRangeFilter['date_from'] = $filter['dateFrom']->format($humanFormat);
        $dateRangeFilter['date_to']   = $filter['dateTo']->format($humanFormat);
        $dateRangeForm                = $this->get('form.factory')->create('daterange', $dateRangeFilter, ['action' => $action]);

        $model->populateWidgetsContent($widgets, $filter);

        return $this->delegateView([
            'viewParameters' => [
                'security'      => $this->get('mautic.security'),
                'widgets'       => $widgets,
                'dateRangeForm' => $dateRangeForm->createView(),
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
     * Generate's new dashboard widget and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
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
        if ($this->request->getMethod() == 'POST') {
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

            $response = new JsonResponse($passthroughVars);

            return $response;
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
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
        if ($this->request->getMethod() == 'POST') {
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

            $response = new JsonResponse($passthroughVars);

            return $response;
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
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $returnUrl = $this->generateUrl('mautic_dashboard_index');
        $success   = 0;
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticDashboardBundle:Dashboard:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dashboard_index',
                'success'       => $success,
                'mauticContent' => 'dashboard',
            ],
        ];

        /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
        $model  = $this->getModel('dashboard');
        $entity = $model->getEntity($objectId);
        if ($entity === null) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.api.client.error.notfound',
                'msgVars' => ['%id%' => $objectId],
            ];
        } else {
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
        }

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    /**
     * Saves the widgets of current user into a json and stores it for later as a file.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveAction()
    {
        // Accept only AJAX POST requests because those are check for CSRF tokens
        if ($this->request->getMethod() !== 'POST' || !$this->request->isXmlHttpRequest()) {
            return $this->accessDenied();
        }

        $name = $this->getNameFromRequest();
        try {
            $this->getModel('dashboard')->saveSnapshot($name);
            $type = 'notice';
            $msg = $this->get('translator')->trans('mautic.dashboard.notice.save', [
                '%name%'    => $name,
                '%viewUrl%' => $this->generateUrl(
                    'mautic_dashboard_action',
                    [
                        'objectAction' => 'import',
                    ]
                )
            ], 'flashes');
        } catch (IOException $e) {
            $type = 'error';
            $this->get('translator')->trans('mautic.dashboard.error.save', [
                '%msg%' => $e->getMessage(),
            ], 'flashes');
        }
        
        return $this->postActionRedirect(
            [
                'flashes' => [
                    [
                        'type' => $type,
                        'msg'  => $msg,
                    ]
                ]
            ]
        );
    }

    /**
     * Exports the widgets of current user into a json file and downloads it.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function exportAction()
    {
        $name     = $this->getNameFromRequest();
        $filename = $this->getModel('dashboard')->sanitizeNameForFileName($name);
        $response = new JsonResponse($this->getModel('dashboard')->toArray($name));
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
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
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
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

        if (file_exists($path) && is_writable($path)) {
            $widgets = json_decode(file_get_contents($path), true);
            if (isset($widgets['widgets'])) {
                $widgets = $widgets['widgets'];
            }

            if ($widgets) {
                /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
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

                return $this->redirect($this->get('router')->generate('mautic_dashboard_index'));
            }
        }

        return $this->redirect($this->generateUrl('mautic_dashboard_action', ['objectAction' => 'import']));
    }

    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function importAction()
    {
        $preview = $this->request->get('preview');

        /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
        $model = $this->getModel('dashboard');

        $directories = [
            'user'   => $this->container->get('mautic.helper.paths')->getSystemPath('dashboard.user'),
            'global' => $this->container->get('mautic.helper.paths')->getSystemPath('dashboard.global'),
        ];

        $action = $this->generateUrl('mautic_dashboard_action', ['objectAction' => 'import']);
        $form   = $this->get('form.factory')->create('dashboard_upload', [], ['action' => $action]);

        if ($this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $fileData = $form['file']->getData();
                    if (!empty($fileData)) {
                        $extension = pathinfo($fileData->getClientOriginalName(), PATHINFO_EXTENSION);
                        if ($extension === 'json') {
                            $fileData->move($directories['user'], $fileData->getClientOriginalName());
                        } else {
                            $form->addError(
                                new FormError(
                                    $this->get('translator')->trans('mautic.core.not.allowed.file.extension', ['%extension%' => $extension], 'validators')
                                )
                            );
                        }
                    } else {
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.dashboard.upload.filenotfound', [], 'validators')
                            )
                        );
                    }
                }
            }
        }

        $dashboardFiles = [];
        $dashboards     = [];

        // User specific layouts
        chdir($directories['user']);
        $dashboardFiles['user'] = glob('*.json');

        // Global dashboards
        chdir($directories['global']);
        $dashboardFiles['global'] = glob('*.json');

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
     * Gets name from request and defaults it to the timestamp if not provided
     *
     * @return string
     */
    private function getNameFromRequest()
    {
        return $this->request->get('name', (new \DateTime())->format('Y-m-dTH:i:s'));
    }
}
