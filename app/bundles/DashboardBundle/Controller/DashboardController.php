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
use Symfony\Component\HttpFoundation\JsonResponse;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\CoreEvents;
use Mautic\DashboardBundle\Entity\Widget;

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
        /** @var \Mautic\DashBundle\Model\DashboardModel $model */
        $model           = $this->factory->getModel('dashboard');
        $widgets         = $model->getWidgets();

        // Apply the default dashboard if no widget exists
        if (!count($widgets)) {
            return $this->applyDashboardFileAction('default.json');
        }

        $action          = $this->generateUrl('mautic_dashboard_index');
        $humanFormat     = 'M j, Y';
        $filterForm      = $this->get('form.factory')->create('dashboard_filter', null, array('action' => $action));

        if ($this->request->isMethod('POST')) {
            $session = $this->factory->getSession();
            $dashboardFilter = $this->request->get('dashboard_filter', array());

            if (!empty($dashboardFilter['date_from'])) {
                $from = new \DateTime($dashboardFilter['date_from']);
                $session->set('mautic.dashboard.date.from', $from->format($humanFormat));
            }

            if (empty($dashboardFilter['date_to'])) {
                $to = new \DateTime($dashboardFilter['date_to']);
                $session->set('mautic.dashboard.date.to', $to->format($humanFormat));
            }
        }

        $filter = $model->getDefaultFilter();
        $model->populateWidgetsContent($widgets, $filter);

        $filterForm->setData(
            array(
                'date_from' => (new \DateTime($filter['dateFrom']))->format($humanFormat),
                'date_to' => (new \DateTime($filter['dateTo']))->format($humanFormat)
            )
        );

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'security'          => $this->factory->getSecurity(),
                'widgets'           => $widgets,
                'filterForm'        => $filterForm->createView()
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

        $model  = $this->factory->getModel('dashboard');
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

        // @todo: build permissions
        // $security    = $this->factory->getSecurity();
        // $permissions = array(
        //     'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner()),
        //     'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getOwner()),
        // );

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
                    // 'permissions' => $permissions,
                ));
                $passthroughVars['widgetId'] = $widget->getId();
                $passthroughVars['widgetWidth'] = $widget->getWidth();
                $passthroughVars['widgetHeight'] = $widget->getHeight();
            }

            $response = new JsonResponse($passthroughVars);
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        } else {

            return $this->delegateView(array(
                'viewParameters'  => array(
                    'form'        => $form->createView(),
                    // 'permissions' => $permissions
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
        $model  = $this->factory->getModel('dashboard');
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

        // @todo: build permissions
        // $security    = $this->factory->getSecurity();
        // $permissions = array(
        //     'edit'   => $security->hasEntityAccess('dashobard:widgets:editown', 'dashobard:widgets:editother', $widget->getOwner()),
        //     'delete' => $security->hasEntityAccess('dashobard:widgets:deleteown', 'dashobard:widgets:deleteown', $widget->getOwner()),
        // );

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
                    // 'permissions' => $permissions,
                ));
                $passthroughVars['widgetId'] = $widget->getId();
                $passthroughVars['widgetWidth'] = $widget->getWidth();
                $passthroughVars['widgetHeight'] = $widget->getHeight();
            }


            $response = new JsonResponse($passthroughVars);
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        } else {

            return $this->delegateView(array(
                'viewParameters'  => array(
                    'form'        => $form->createView(),
                    // 'permissions' => $permissions
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
        // @todo: build permissions
        // if (!$this->factory->getSecurity()->isGranted('dashobard:widgets:delete')) {
        //     return $this->accessDenied();
        // }

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
        $model  = $this->factory->getModel('dashboard');
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
        /** @var \Mautic\DashBundle\Model\DashboardModel $model */
        $model = $this->factory->getModel('dashboard');
        $widgetsPaginator = $model->getWidgets();
        $widgets = array();

        foreach ($widgetsPaginator as $widget) {
            $widgets[] = array(
                'name'      => $widget->getName(),
                'width'     => $widget->getWidth(),
                'height'    => $widget->getHeight(),
                'ordering'  => $widget->getOrdering(),
                'type'      => $widget->getType(),
                'params'    => $widget->getParams(),
                'template'  => $widget->getTemplate(),
            );
        }

        $name = 'dashboard-of-' . str_replace(' ', '-', $this->factory->getUser()->getName()) . '-' . (new \DateTime)->format('Y-m-dTH:i:s');

        $response = new JsonResponse($widgets);
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);
        $response->headers->set('Content-Length', strlen($response->getContent()));
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $name . '.json"');
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
        $dir  = $this->factory->getParameter('dashboard_import_dir');
        $path = $dir . '/' . $file;

        if (file_exists($path) && is_writable($path)) {
            unlink($path);
        }

        return $this->importAction();
    }

    /**
     * Exports the widgets of current user into a json file
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function applyDashboardFileAction($file = null)
    {
        if (!$file) {
            $file = $this->request->get('file');
        }

        $dir = $this->factory->getParameter('dashboard_import_dir');
        $path = $dir . '/' . $file;

        if (file_exists($path) && is_writable($path)) {
            $widgets = json_decode(file_get_contents($path), true);

            if ($widgets) {
                /** @var \Mautic\DashBundle\Model\DashboardModel $model */
                $model = $this->factory->getModel('dashboard');

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

                return $this->indexAction();
            }
        }

        return $this->importAction();
    }

    /**
     * @param int  $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function importAction()
    {
        $preview = $this->request->get('preview');

        /** @var \Mautic\DashBundle\Model\DashboardModel $model */
        $model = $this->factory->getModel('dashboard');
        $dir = $this->factory->getParameter('dashboard_import_dir');
        $session = $this->factory->getSession();

        // @todo implement permissions
        // if (!$this->factory->getSecurity()->isGranted('dashboard:widgets:create')) {
        //     return $this->accessDenied();
        // }

        $action     = $this->generateUrl('mautic_dashboard_action', array('objectAction' => 'import'));
        $form       = $this->get('form.factory')->create('dashboard_upload', array(), array('action' => $action));

        if ($this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $fileData = $form['file']->getData();
                    if (!empty($fileData)) {
                        
                        // @todo check is_writable
                        if (!is_dir($dir) && !file_exists($dir)) {
                            mkdir($dir);
                        }

                        $fileData->move($dir, $fileData->getClientOriginalName());
                    } else {
                        $form->addError(
                            new FormError(
                                $this->factory->getTranslator()->trans('mautic.dashboard.upload.filenotfound', array(), 'validators')
                            )
                        );
                    }
                }
            }
        }

        $dashboards = array_diff(scandir($dir), array('..', '.'));

        if (!$dashboards) {
            $dashboards = array();
        }

        if ($preview && ($dashId = array_search($preview, $dashboards))) {
            // @todo check is_writable
            $widgets = json_decode(file_get_contents($dir . '/' . $dashboards[$dashId]), true);
            $filter = $model->getDefaultFilter();
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
