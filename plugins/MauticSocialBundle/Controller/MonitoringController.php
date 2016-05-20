<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;

/**
 * Class MonitoringController
 */

class MonitoringController extends FormController
{
    /*
     * @param int $page
     */
    public function indexAction($page = 1)
    {
        $session = $this->get('session');

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model = $this->factory->getModel('plugin.mauticSocial.monitoring');

        //set limits
        $limit = $session->get('mautic.social.monitoring.limit', $this->container->getParameter('mautic.default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.social.monitoring.filter', ''));
        $session->set('mautic.social.monitoring.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        $orderBy    = $session->get('mautic.social.monitoring.orderby', 'e.title');
        $orderByDir = $session->get('mautic.social.monitoring.orderbydir', 'DESC');

        $monitoringList = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($monitoringList);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current asset so redirect to the last asset
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $session->set('mautic.social.monitoring.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_social_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticSocialBundle:Monitoring:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_social_index',
                    'mauticContent' => 'monitoring'
                )
            ));
        }

        //set what asset currently on so that we can return here after form submission/cancellation
        $session->set('mautic.social.monitoring.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  => array(
                'searchValue' => $search,
                'items'       => $monitoringList,
                'limit'       => $limit,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'page'        => $page,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticSocialBundle:Monitoring:list.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_social_index',
                'mauticContent' => 'monitoring',
                'route'         => $this->generateUrl('mautic_social_index', array('page' => $page))
            )
        ));
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->factory->getSecurity()->isGranted('plugin:mauticSocial:monitoring:create')) {
            return $this->accessDenied();
        }

        $action  = $this->generateUrl('mautic_social_action', array('objectAction' => 'new'));

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model   = $this->factory->getModel('plugin.mauticSocial.monitoring');

        $entity  = $model->getEntity();
        $method  = $this->request->getMethod();
        $session = $this->get('session');

        // get the list of types from the model
        $networkTypes  = $model->getNetworkTypes();

        // get the network type from the request on submit. helpful for validation error
        // rebuilds structure of the form when it gets updated on submit
        $networkType = ($this->request->getMethod() == 'POST') ? $this->request->request->get('monitoring[networkType]', '', true) : '';

        // build the form
        $form    = $model->createForm($entity, $this->get('form.factory'), $action, array(
                // pass through the types and the selected default type
                'networkTypes'   => $networkTypes,
                'networkType'   => $networkType
        ));

        // Set the page we came from
        $page = $session->get('mautic.social.monitoring.page', 1);
        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $viewParameters = array('page' => $page);
            $template       = 'MauticSocialBundle:Monitoring:index';

            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    // update the audit log
                    $this->updateAuditLog($entity, 'create');

                    $this->addFlash('mautic.core.notice.created', array(
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_social_index',
                        '%url%'       => $this->generateUrl('mautic_social_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                    ));

                    if (!$form->get('buttons')->get('save')->isClicked()) {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }

                    $viewParameters = array(
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId()
                    );
                    $template       = 'MauticSocialBundle:Monitoring:view';
                }
            }
            $returnUrl = $this->generateUrl('mautic_social_index', $viewParameters);

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => array(
                            'activeLink'    => 'mautic_social_index',
                            'mauticContent' => 'monitoring'
                        )
                    ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $form->createView(),
            ),
            'contentTemplate' => 'MauticSocialBundle:Monitoring:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_social_index',
                'mauticContent' => 'monitoring',
                'route'         => $this->generateUrl('mautic_social_action', array(
                    'objectAction' => 'new',
                    'objectId'     => $entity->getId()
                ))
            )
        ));
    }

    /**
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        if (!$this->factory->getSecurity()->isGranted('plugin:mauticSocial:monitoring:edit')) {
            return $this->accessDenied();
        }

        $action  = $this->generateUrl('mautic_social_action', array('objectAction' => 'edit', 'objectId' => $objectId));

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model   = $this->factory->getModel('plugin.mauticSocial.monitoring');

        $entity  = $model->getEntity($objectId);
        $session = $this->factory->getSession();

        // Set the page we came from
        $page   = $session->get('mautic.social.monitoring.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_social_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticSocial:Monitoring:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_social_index',
                'mauticContent' => 'monitoring'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                        'flashes' => array(
                            array(
                                'type'    => 'error',
                                'msg'     => 'mautic.social.monitoring.error.notfound',
                                'msgVars' => array('%id%' => $objectId)
                            )
                        )
                    ))
            );
        }

        // get the list of types from the model
        $networkTypes  = $model->getNetworkTypes();

        // get the network type from the request on submit. helpful for validation error
        // rebuilds structure of the form when it gets updated on submit
        $networkType = ($this->request->getMethod() == 'POST') ? $this->request->request->get('monitoring[networkType]', '', true) : $entity->getNetworkType();

        // build the form
        $form  = $model->createForm($entity, $this->get('form.factory'), $action, array(
                // pass through the types and the selected default type
                'networkTypes'   => $networkTypes,
                'networkType'   => $networkType
            ));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    // update the audit log
                    $this->updateAuditLog($entity, 'update');

                    $this->addFlash('mautic.core.notice.updated', array(
                            '%name%'      => $entity->getTitle(),
                            '%menu_link%' => 'mautic_email_index',
                            '%url%'       => $this->generateUrl('mautic_social_action', array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId()
                                ))
                        ), 'warning');
                }
            } else {
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                $viewParameters = array(
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId()
                );

                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_social_action', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'MauticSocialBundle:Monitoring:view'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $form->createView(),
            ),
            'contentTemplate' => 'MauticSocialBundle:Monitoring:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_social_index',
                'mauticContent' => 'monitoring',
                'route'         => $this->generateUrl('mautic_social_action', array(
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId()
                    ))
            )
        ));
    }

    /**
     * Loads a specific form into the detailed panel
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction ($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('plugin:mauticSocial:monitoring:view')) {
            return $this->accessDenied();
        }

        $session = $this->get('session');

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model = $this->factory->getModel('plugin.mauticSocial.monitoring');

        /** @var \MauticPlugin\MauticSocialBundle\Entity\PostCountRepository $postCountRepo */
        $postCountRepo      = $this->factory->getModel('plugin.mauticSocial.PostCount')->getRepository();

        $security         = $this->factory->getSecurity();
        $monitoringEntity = $model->getEntity($objectId);

        //set the asset we came from
        $page = $session->get('mautic.social.monitoring.page', 1);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'details') : 'details';

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject('monitoring', $objectId);

        $leadStats = $postCountRepo->getLeadStatsPost(30, 'D', array('monitor_id' => $monitoringEntity->getId()));


        if ($monitoringEntity === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_social_index', array('page' => $page));

            return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticSocialMonitoringBundle:Monitoring:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_social_index',
                        'mauticContent' => 'monitoring'
                    ),
                    'flashes'         => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.social.monitoring.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ));
        }

        return $this->delegateView(array(
                'returnUrl'       => $this->generateUrl('mautic_social_action', array(
                        'objectAction' => 'view',
                        'objectId'     => $monitoringEntity->getId())
                ),
                'viewParameters'  => array(
                    'activeMonitoring'   => $monitoringEntity,
                    'logs'               => $logs,
                    'tmpl'               => $tmpl,
                    'security'           => $security,
                    'leadStats'          => $leadStats,
                    'monitorLeads'       => $this->forward('MauticSocialBundle:Monitoring:leads', array(
                            'objectId'   => $monitoringEntity->getId(),
                            'page'       => $page,
                            'ignoreAjax' => true
                        ))->getContent()
                ),
                'contentTemplate' => 'MauticSocialBundle:Monitoring:' . $tmpl . '.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_social_index',
                    'mauticContent' => 'monitoring'
                )
            ));
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction ($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('plugin:mauticSocial:monitoring:delete')) {
            return $this->accessDenied();
        }

        $session   = $this->get('session');
        $page      = $session->get('mautic.social.monitoring.page', 1);
        $returnUrl = $this->generateUrl('mautic_social_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticSocialBundle:Monitoring:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_social_index',
                'mauticContent' => 'monitoring'
            )
        );

        if ($this->request->getMethod() == 'POST') {

            /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
            $model  = $this->factory->getModel('plugin.mauticSocial.monitoring');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.social.monitoring.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'plugin.mauticSocial.monitoring');
            }

            // update the audit log
            $this->updateAuditLog($entity, 'delete');

            // then delete the record
            $model->deleteEntity($entity);

            $flashes[] = array(
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $entity->getTitle(),
                    '%id%'   => $objectId
                )
            );
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                    'flashes' => $flashes
                ))
        );
    }

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        if (!$this->factory->getSecurity()->isGranted('plugin:mauticSocial:monitoring:delete')) {
            return $this->accessDenied();
        }

        $session     = $this->get('session');
        $page        = $session->get('mautic.social.monitoring.page', 1);
        $returnUrl   = $this->generateUrl('mautic_social_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticSocialBundle:Monitoring:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_social_index',
                'mauticContent' => 'monitoring'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
            $model = $this->factory->getModel('plugin.mauticSocial.monitoring');

            $ids       = json_decode($this->request->query->get('ids', ''));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.social.monitoring.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'monitoring', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.social.monitoring.notice.batch_deleted',
                    'msgVars' => array(
                        '%count%' => count($entities)
                    )
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                    'flashes' => $flashes
                ))
        );
    }

    /**
     * @param     $objectId
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function leadsAction ($objectId, $page = 1)
    {
        $session = $this->get('session');
        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $session->get('mautic.social.monitor.lead.limit', $this->container->getParameter('mautic.default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.social.monitor.lead.filter', ''));
        $session->set('mautic.campaign.lead.filter', $search);

        $orderBy    = $session->get('mautic.social.monitor.lead.orderby', 'l.date_added');
        $orderByDir = $session->get('mautic.social.monitor.lead.orderbydir', 'ASC');
        $filter     = array('string' => $search, 'force' => array());

        /** @var \MauticPlugin\MauticSocialBundle\Entity\LeadRepository $monitoringLeadRepo */
        $monitoringLeadRepo = $this->getDoctrine()->getManager()->getRepository('MauticSocialBundle:Lead');

        $leads = $monitoringLeadRepo->getLeadsWithFields(
            array(
                'monitor_id'    => $objectId,
                'withTotalCount' => true,
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => $filter,
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir
            )
        );

        // We need the EmailRepository to check if a lead is flagged as do not contact
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo = $this->factory->getModel('email')->getRepository();

        $count = $leads['count'];

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $session->set('mautic.social.monitor.lead.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_social_leads', array('objectId' => $objectId, 'page' => $lastPage));

            return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $lastPage, 'objectId' => $objectId),
                    'contentTemplate' => 'MauticLeadBundle:Lead:grid.html.php',
                    'passthroughVars' => array(
                        'mauticContent' => 'monitorLeads'
                    )
                ));
        }


        return $this->delegateView(array(
            'viewParameters'  => array(
                'page'          => $page,
                'items'         => $leads['results'],
                'totalItems'    => $leads['count'],
                'tmpl'          => 'monitorleads',
                'indexMode'     => 'grid',
                'link'          => 'mautic_social_leads',
                'sessionVar'    => 'social.monitor.lead',
                'limit'         => $limit,
                'objectId'      => $objectId,
                'noContactList' => $emailRepo->getDoNotEmailList()
            ),
            'contentTemplate' => 'MauticSocialBundle:Monitoring:leads.html.php',
            'passthroughVars' => array(
                'mauticContent' => 'monitorLeads',
                'route'         => false
            )
        ));
    }

    /*
     * Update the audit log
     */
    public function updateAuditLog(Monitoring $monitoring, $action)
    {
        $log = array(
            "bundle"     => "plugin.mauticSocial",
            "object"     => "monitoring",
            "objectId"   => $monitoring->getId(),
            "action"     => $action,
            "details"    => array('name' => $monitoring->getTitle()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );

        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}