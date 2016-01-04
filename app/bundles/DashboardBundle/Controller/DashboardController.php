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
        /** @var \Mautic\PageBundle\Entity\HitRepository $hitRepo */
        $hitRepo        = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
        /** @var \Mautic\EmailBundle\Entity\StatRepository $emailStatRepo */
        $emailStatRepo  = $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');
        /** @var \Mautic\CoreBundle\Entity\IpAddressRepository $ipAddressRepo */
        $ipAddressRepo  = $this->factory->getEntityManager()->getRepository('MauticCoreBundle:IpAddress');
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo      = $this->factory->getModel('email')->getRepository();

        $sentReadCount        = $emailRepo->getSentReadCount();
        $clickthroughCount    = $hitRepo->countEmailClickthrough();
        $newReturningVisitors = array(
            'returning' => $hitRepo->countReturningIp(),
            'unique'    => $ipAddressRepo->countIpAddresses()
        );
        $weekVisitors         = $hitRepo->countVisitors(604800);
        $allTimeVisitors      = $hitRepo->countVisitors(0);
        $allSentEmails        = $emailStatRepo->getSentCount();

        /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
        $pageModel            = $this->factory->getModel('page');
        $popularPages   = $pageModel->getRepository()->getPopularPages();

        $popularAssets = $this->factory->getModel('asset')->getRepository()->getPopularAssets();

        $popularCampaigns = $this->factory->getModel('campaign')->getRepository()->getPopularCampaigns();

        $returnRate = 0;
        $totalVisits = array_sum($newReturningVisitors);
        if ($totalVisits > 0) {
            $returnRate = round($newReturningVisitors['returning'] / $totalVisits * 100);
        }

        $clickRate = 0;

        if ($sentReadCount['sent_count'] > 0) {
            $clickRate = round($clickthroughCount / $sentReadCount['sent_count'] * 100);
        }

        /** @var \Mautic\PageBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');
        $mapData   = $leadModel->getLeadMapData();

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject(null, null);

        // Get names of log's items
        $router = $this->factory->getRouter();
        foreach ($logs as $key => &$log) {
            if (!empty($log['bundle']) && !empty($log['object']) && !empty($log['objectId'])) {
                try {
                    $model = $this->factory->getModel($log['bundle'].'.'.$log['object']);
                    $item  = $model->getEntity($log['objectId']);
                    if (method_exists($item, $model->getNameGetter())) {
                        $log['objectName'] = $item->{$model->getNameGetter()}();

                        if ($log['bundle'] == 'lead' && $log['objectName'] == 'mautic.lead.lead.anonymous') {
                            $log['objectName'] = $this->factory->getTranslator()->trans('mautic.lead.lead.anonymous');
                        }
                    } else {
                        $log['objectName'] = '';
                    }

                    $routeName = 'mautic_'.$log['bundle'].'_action';
                    if ($router->getRouteCollection()->get($routeName) !== null) {
                        $log['route'] = $router->generate(
                            'mautic_'.$log['bundle'].'_action',
                            array('objectAction' => 'view', 'objectId' => $log['objectId'])
                        );
                    } else {
                        $log['route'] = false;
                    }
                } catch (\Exception $e) {
                    unset($logs[$key]);
                }
            }
        }

        $event = new IconEvent($this->factory->getSecurity());
        $this->factory->getDispatcher()->dispatch(CoreEvents::FETCH_ICONS, $event);
        $icons = $event->getIcons();

        // Upcoming emails from Campaign Bundle
        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');
        $upcomingEmails = $leadEventLogRepository->getUpcomingEvents(array('type' => 'email.send', 'scheduled' => 1, 'eventType' => 'action'));

        $leadModel = $this->factory->getModel('lead.lead');

        foreach ($upcomingEmails as &$email) {
            $email['lead'] = $leadModel->getEntity($email['lead_id']);
        }

        /** @var \Mautic\DashBundle\Model\DashboardModel $pageModel */
        $model = $this->factory->getModel('dashboard');

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'sentReadCount'     => $sentReadCount,
                'clickthroughCount' => $clickthroughCount,
                'returnRate'        => $returnRate,
                'clickRate'         => $clickRate,
                'newReturningVisitors' => $newReturningVisitors,
                'weekVisitors'      => $weekVisitors,
                'allTimeVisitors'   => $allTimeVisitors,
                'popularPages'      => $popularPages,
                'popularAssets'     => $popularAssets,
                'popularCampaigns'  => $popularCampaigns,
                'allSentEmails'     => $allSentEmails,
                'mapData'           => $mapData,
                'logs'              => $logs,
                'icons'             => $icons,
                'upcomingEmails'    => $upcomingEmails,
                'leadLineChart'     => $leadModel->getLeadsLineChartData(30, 'd'),
                'security'          => $this->factory->getSecurity(),
                'widgets'           => $model->getWidgets()
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

            $model->populateWidgetContent($widget);

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

            $model->populateWidgetContent($widget);

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
}
