<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Mautic\DashboardBundle\Entity\Widget;
use Symfony\Component\Form\FormError;

/**
 * Class DashboardController
 */
class DashboardController extends FormController
{

    /**
     * Generates the default view
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
        $dateRangeFilter = $this->request->get('daterange', array());


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
        $filter          = $model->getDefaultFilter();

        // Set the final date range to the form
        $dateRangeFilter['date_from'] = $filter['dateFrom']->format($humanFormat);
        $dateRangeFilter['date_to'] = $filter['dateTo']->format($humanFormat);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeFilter, array('action' => $action));

        $model->populateWidgetsContent($widgets, $filter);

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'security'          => $this->get('mautic.security'),
                'widgets'           => $widgets,
                'dateRangeForm'     => $dateRangeForm->createView()
            ),
            'contentTemplate' => 'MauticDashboardBundle:Dashboard:index.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_dashboard_index',
                'mauticContent'  => 'dashboard',
                'route'          => $this->generateUrl('mautic_dashboard_index')
            )
        ));
    }

    /**
     * Generate's new dashboard widget and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        //retrieve the entity
        $widget = new Widget();

        $model  = $this->getModel('dashboard');
        $action = $this->generateUrl('mautic_dashboard_action', array('objectAction' => 'new'));

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
            $passthroughVars = array(
                'closeModal'    => 1,
                'mauticContent' => 'widget'
            );

            $filter = $model->getDefaultFilter();
            $model->populateWidgetContent($widget, $filter);

            if ($valid && !$cancelled) {
                $passthroughVars['upWidgetCount'] = 1;
                $passthroughVars['widgetHtml'] = $this->renderView('MauticDashboardBundle:Widget:detail.html.php', array(
                    'widget'      => $widget,
                ));
                $passthroughVars['widgetId'] = $widget->getId();
                $passthroughVars['widgetWidth'] = $widget->getWidth();
                $passthroughVars['widgetHeight'] = $widget->getHeight();
            }

            $response = new JsonResponse($passthroughVars);

            return $response;
        } else {

            return $this->delegateView(array(
                'viewParameters'  => array(
                    'form'        => $form->createView()
                ),
                'contentTemplate' => 'MauticDashboardBundle:Widget:form.html.php'
            ));
        }
    }

    /**
     * edit widget and processes post data
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($objectId)
    {
        $model  = $this->getModel('dashboard');
        $widget = $model->getEntity($objectId);
        $action = $this->generateUrl('mautic_dashboard_action', array('objectAction' => 'edit', 'objectId' => $objectId));

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
            $passthroughVars = array(
                'closeModal'    => 1,
                'mauticContent' => 'widget'
            );

            $filter = $model->getDefaultFilter();
            $model->populateWidgetContent($widget, $filter);

            if ($valid && !$cancelled) {
                $passthroughVars['upWidgetCount'] = 1;
                $passthroughVars['widgetHtml'] = $this->renderView('MauticDashboardBundle:Widget:detail.html.php', array(
                    'widget'      => $widget,
                ));
                $passthroughVars['widgetId'] = $widget->getId();
                $passthroughVars['widgetWidth'] = $widget->getWidth();
                $passthroughVars['widgetHeight'] = $widget->getHeight();
            }


            $response = new JsonResponse($passthroughVars);

            return $response;
        } else {

            return $this->delegateView(array(
                'viewParameters'  => array(
                    'form'        => $form->createView(),
                ),
                'contentTemplate' => 'MauticDashboardBundle:Widget:form.html.php'
            ));
        }
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $returnUrl = $this->generateUrl('mautic_dashboard_index');
        $success   = 0;
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticDashboardBundle:Dashboard:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_dashboard_index',
                'success'       => $success,
                'mauticContent' => 'dashboard'
            )
        );

        /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
        $model  = $this->getModel('dashboard');
        $entity = $model->getEntity($objectId);
        if ($entity === null) {
            $flashes[] = array(
                'type'    => 'error',
                'msg'     => 'mautic.api.client.error.notfound',
                'msgVars' => array('%id%' => $objectId)
            );
        } else {
            $model->deleteEntity($entity);
            $name      = $entity->getName();
            $flashes[] = array(
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $name,
                    '%id%'   => $objectId
                )
            );
        }

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                array(
                    'flashes' => $flashes
                )
            )
        );
    }

    /**
     * Exports the widgets of current user into a json file
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function exportAction()
    {
        /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
        $model            = $this->getModel('dashboard');
        $widgetsPaginator = $model->getWidgets();
        $usersName        = $this->user->getName();
        $dateTime         = new \DateTime;
        $dateStamp        = $dateTime->format('Y-m-d H:i:s');
        $name             = $this->request->get(
            'name',
            'dashboard-of-'.str_replace(' ', '-', $usersName).'-'. $dateStamp
        );

        $description      = $this->get('translator')->trans(
            'mautic.dashboard.generated_by',
            array(
                '%name%' => $usersName,
                '%date%' => $dateStamp
            )
        );

        $dashboard = array(
            'name'        => $name,
            'description' => $description,
            'widgets'     => array()
        );

        foreach ($widgetsPaginator as $widget) {
            $dashboard['widgets'][] = array(
                'name'     => $widget->getName(),
                'width'    => $widget->getWidth(),
                'height'   => $widget->getHeight(),
                'ordering' => $widget->getOrdering(),
                'type'     => $widget->getType(),
                'params'   => $widget->getParams(),
                'template' => $widget->getTemplate(),
            );
        }

        // Make the filename safe
        $filename = InputHelper::alphanum($name, false, '_'). '.json';

        if ($this->request->get('save', false)) {
            // Save to the user's folder
            $dir = $this->factory->getSystemPath('dashboard.user');
            file_put_contents($dir . '/' . $filename, json_encode($dashboard));

            return $this->redirect($this->get('router')->generate('mautic_dashboard_action', array('objectAction' => 'import')));
        }

        $response = new JsonResponse($dashboard);
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
     * Exports the widgets of current user into a json file
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteDashboardFileAction()
    {
        $file = $this->request->get('file');

        $parts = explode('.', $file);
        $type  = array_shift($parts);
        $name  = implode('.', $parts);

        $dir  = $this->factory->getSystemPath("dashboard.$type");
        $path = $dir.'/'.$name.'.json';


        if (file_exists($path) && is_writable($path)) {
            unlink($path);
        }

        return $this->redirect($this->generateUrl('mautic_dashboard_action', array('objectAction' => 'import')));
    }

    /**
     * Applies dashboard layout
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

        $dir  = $this->factory->getSystemPath("dashboard.$type");
        $path = $dir . '/' . $name . '.json';

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

        return $this->redirect($this->generateUrl('mautic_dashboard_action', array('objectAction' => 'import')));
    }

    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function importAction()
    {
        $preview = $this->request->get('preview');

        /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
        $model = $this->getModel('dashboard');

        $directories = array(
            'user'   => $this->factory->getSystemPath('dashboard.user'),
            'global' => $this->factory->getSystemPath('dashboard.global')
        );

        $action = $this->generateUrl('mautic_dashboard_action', array('objectAction' => 'import'));
        $form   = $this->get('form.factory')->create('dashboard_upload', array(), array('action' => $action));

        if ($this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $fileData = $form['file']->getData();
                    if (!empty($fileData)) {
                        $fileData->move($directories['user'], $fileData->getClientOriginalName());
                    } else {
                        $form->addError(
                            new FormError(
                                $this->translator->trans('mautic.dashboard.upload.filenotfound', array(), 'validators')
                            )
                        );
                    }
                }
            }
        }

        $dashboardFiles = array();
        $dashboards     = array();

        // User specific layouts
        chdir($directories['user']);
        $dashboardFiles['user'] = glob('*.json');

        // Global dashboards
        chdir($directories['global']);
        $dashboardFiles['global'] = glob('*.json');

        foreach ($dashboardFiles as $type => $dirDashboardFiles) {
            $tempDashboard = array();
            foreach ($dirDashboardFiles as $dashId => $dashboard) {
                $dashboard = str_replace('.json', '', $dashboard);
                $config    = json_decode(
                    file_get_contents($directories[$type].'/'.$dirDashboardFiles[$dashId]),
                    true
                );

                // Check for name, description, etc
                $tempDashboard[$dashboard] = array(
                    'type'        => $type,
                    'name'        => (isset($config['name'])) ? $config['name'] : $dashboard,
                    'description' => (isset($config['description'])) ? $config['description'] : '',
                    'widgets'     => (isset($config['widgets'])) ? $config['widgets'] : $config
                );
            }

            // Sort by name
            uasort($tempDashboard,
                function($a, $b) {

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
            $widgets = array();
        }

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'form'       => $form->createView(),
                    'dashboards' => $dashboards,
                    'widgets'    => $widgets,
                    'preview'    => $preview
                ),
                'contentTemplate' => 'MauticDashboardBundle:Dashboard:import.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_dashboard_index',
                    'mauticContent' => 'dashboardImport',
                    'route'         => $this->generateUrl(
                        'mautic_dashboard_action',
                        array(
                            'objectAction' => 'import'
                        )
                    )
                )
            )
        );
    }
}
