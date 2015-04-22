<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CampaignController extends FormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction ($page = 1)
    {
        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'campaign:campaigns:view',
            'campaign:campaigns:create',
            'campaign:campaigns:edit',
            'campaign:campaigns:delete',
            'campaign:campaigns:publish'

        ), "RETURN_ARRAY");

        if (!$permissions['campaign:campaigns:view']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.campaign.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.campaign.filter', ''));
        $this->factory->getSession()->set('mautic.campaign.filter', $search);

        $filter     = array('string' => $search, 'force' => array());
        $orderBy    = $this->factory->getSession()->get('mautic.campaign.orderby', 'c.name');
        $orderByDir = $this->factory->getSession()->get('mautic.campaign.orderbydir', 'ASC');

        $campaigns = $this->factory->getModel('campaign')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            )
        );

        $count = count($campaigns);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $this->factory->getSession()->set('mautic.campaign.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_campaign_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.campaign.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  => array(
                'searchValue' => $search,
                'items'       => $campaigns,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticCampaignBundle:Campaign:list.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign',
                'route'         => $this->generateUrl('mautic_campaign_index', array('page' => $page))
            )
        ));
    }

    /**
     * View a specific campaign
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction ($objectId)
    {
        $tmpl = $this->request->get('tmpl', 'index');

        if ($tmpl == 'campaignleads') {
            //forward to leadsAction
            $page  = $this->factory->getSession()->get('mautic.campaign.lead.page', 1);
            $query = array("ignoreAjax" => true, 'request' => $this->request);
            return $this->forward('MauticCampaignBundle:Campaign:leads', array('objectId' => $objectId, 'page' => $page, $query));
        }

        $page  = $this->factory->getSession()->get('mautic.campaign.page', 1);

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model    = $this->factory->getModel('campaign');
        $security = $this->factory->getSecurity();
        $entity   = $model->getEntity($objectId);

        $permissions = $security->isGranted(array(
            'campaign:campaigns:view',
            'campaign:campaigns:create',
            'campaign:campaigns:edit',
            'campaign:campaigns:delete',
            'campaign:campaigns:publish'
        ), "RETURN_ARRAY");


        if ($entity === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_campaign_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.campaign.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$permissions['campaign:campaigns:view']) {
            return $this->accessDenied();
        }

        $campaignLeadRepo = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:Lead');
        $eventLogRepo     = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');
        $events       = $model->getEventRepository()->getCampaignEvents($entity->getId());

        $campaignLeads = $model->getRepository()->getCampaignLeadIds($entity->getId());

        $leadCount     = count($campaignLeads);
        $campaignLogs  = $eventLogRepo->getCampaignLogCounts($entity->getId(), $campaignLeads);

        foreach ($events as &$event) {
            $event['logCount'] = 0;
            $event['percent']  = 0;
            if (isset($campaignLogs[$event['id']])) {
                $event['logCount'] = $campaignLogs[$event['id']];
            }
            if ($leadCount) {
                $event['percent'] = round($event['logCount'] / $leadCount * 100);
            }
        }

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject('campaign', $objectId);

        // Hit count per day for last 30 days
        $hits = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit')->getHits(30, 'D', array('source_id' => $entity->getId(), 'source' => 'campaign'));

        // Sent emails stats
        $emailsSent = $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat')->getIgnoredReadFailed(null, array('source_id' => $entity->getId(), 'source' => 'campaign'));

        // Lead count stats
        $leadStats = $campaignLeadRepo->getLeadStats(30, 'D', array('campaign_id' => $entity->getId()));

        $leadPage = $this->factory->getSession()->get('mautic.campaign.lead.page', 1);

        return $this->delegateView(array(
            'viewParameters'    => array(
                'campaign'      => $entity,
                'permissions'   => $permissions,
                'security'      => $security,
                'logs'          => $logs,
                'hits'          => $hits,
                'emailsSent'    => $emailsSent,
                'leadStats'     => $leadStats,
                'events'        => $events,
                'campaignLeads' => $this->forward('MauticCampaignBundle:Campaign:leads', array(
                    'objectId'   => $entity->getId(),
                    'page'       => $leadPage,
                    'ignoreAjax' => true
                ))->getContent()
            ),
            'contentTemplate' => 'MauticCampaignBundle:Campaign:details.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign',
                'route'         => $this->generateUrl('mautic_campaign_action', array(
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId())
                )
            )
        ));
    }

    /**
     * @param     $objectId
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function leadsAction ($objectId, $page = 1)
    {
        if (!$this->factory->getSecurity()->isGranted('campaign:campaigns:view')) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.campaign.lead.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.campaign.lead.filter', ''));
        $this->factory->getSession()->set('mautic.campaign.lead.filter', $search);

        $filter     = array('string' => $search, 'force' => array());
        $orderBy    = $this->factory->getSession()->get('mautic.campaign.lead.orderby', 'l.date_added');
        $orderByDir = $this->factory->getSession()->get('mautic.campaign.lead.orderbydir', 'ASC');

        // We need the EmailRepository to check if a lead is flagged as do not contact
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo = $this->factory->getModel('email')->getRepository();

        $campaignLeadRepo = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:Lead');
        $leads            = $campaignLeadRepo->getLeadsWithFields(
            array(
                'campaign_id'    => $objectId,
                'withTotalCount' => true,
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => $filter,
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir
            )
        );

        $count = $leads['count'];
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $this->factory->getSession()->set('mautic.campaign.lead.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_campaign_leads', array('objectId' => $objectId, 'page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage, 'objectId' => $objectId),
                'contentTemplate' => 'MauticLeadBundle:Lead:grid.html.php',
                'passthroughVars' => array(
                    'mauticContent' => 'campaignLeads'
                )
            ));
        }

        $triggerModel = $this->factory->getModel('point.trigger');
        foreach ($leads['results'] as &$l) {
            $l['color'] = $triggerModel->getColorForLeadPoints($l['points']);
        }
        return $this->delegateView(array(
            'viewParameters'  => array(
                'page'          => $page,
                'items'         => $leads['results'],
                'totalItems'    => $leads['count'],
                'tmpl'          => 'campaignleads',
                'indexMode'     => 'grid',
                'link'          => 'mautic_campaign_leads',
                'sessionVar'    => 'campaign.lead',
                'limit'         => $limit,
                'objectId'      => $objectId,
                'noContactList' => $emailRepo->getDoNotEmailList()
            ),
            'contentTemplate' => 'MauticCampaignBundle:Campaign:leads.html.php',
            'passthroughVars' => array(
                'mauticContent' => 'campaignLeads',
                'route'         => false
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
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model = $this->factory->getModel('campaign');

        $entity  = $model->getEntity();
        $session = $this->factory->getSession();

        if (!$this->factory->getSecurity()->isGranted('campaign:campaigns:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.campaign.page', 1);

        $sessionId = $this->request->request->get('campaign[sessionId]', sha1(uniqid(mt_rand(), true)), true);

        list($modifiedEvents, $deletedEvents, $events) = $this->getSessionEvents($sessionId);

        $action        = $this->generateUrl('mautic_campaign_action', array('objectAction' => 'new'));
        $form          = $model->createForm($entity, $this->get('form.factory'), $action);
        $eventSettings = $model->getEvents();

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //make sure that at least one action is selected
                    if (empty($events)) {
                        //set the error
                        $form->addError(new FormError(
                            $this->get('translator')->trans('mautic.campaign.form.events.notempty', array(), 'validators')
                        ));
                        $valid = false;
                    } else {
                        $connections = $session->get('mautic.campaign.' . $sessionId . '.events.canvassettings');
                        $model->setEvents($entity, $events, $connections, $deletedEvents);

                        //form is valid so process the data
                        $model->saveEntity($entity);

                        //update canvas settings with new event IDs then save
                        $model->setCanvasSettings($entity, $connections);

                        $this->addFlash('mautic.core.notice.created', array(
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_campaign_index',
                            '%url%'       => $this->generateUrl('mautic_campaign_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ));

                        if ($form->get('buttons')->get('save')->isClicked()) {
                            $viewParameters = array(
                                'objectAction' => 'view',
                                'objectId'     => $entity->getId()
                            );
                            $returnUrl      = $this->generateUrl('mautic_campaign_action', $viewParameters);
                            $template       = 'MauticCampaignBundle:Campaign:view';
                        } else {
                            //return edit view so that all the session stuff is loaded
                            return $this->editAction($entity->getId(), true);
                        }
                    }
                } else {
                    $connections = $session->get('mautic.campaign.' . $sessionId . '.events.canvassettings');
                    $model->setCanvasSettings($entity, $connections, false, $modifiedEvents);
                }
            } else {
                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl('mautic_campaign_index', $viewParameters);
                $template       = 'MauticCampaignBundle:Campaign:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                //clear temporary fields
                $this->clearSessionComponents($sessionId);

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_campaign_index',
                        'mauticContent' => 'campaign'
                    )
                ));
            }
        } else {
            //clear out existing fields in case the form was refreshed, browser closed, etc
            $this->clearSessionComponents($sessionId);
            $modifiedEvents = $deletedEvents = array();

            $form->get('sessionId')->setData($sessionId);
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'eventSettings'  => $eventSettings,
                'campaignEvents' => $modifiedEvents,
                'deletedEvents'  => $deletedEvents,
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $form->createView()
            ),
            'contentTemplate' => 'MauticCampaignBundle:Campaign:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign',
                'route'         => $this->generateUrl('mautic_campaign_action', array(
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                        'objectId'     => $entity->getId())
                )
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model = $this->factory->getModel('campaign');

        $entity     = $model->getEntity($objectId);
        $session    = $this->factory->getSession();

        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.campaign.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_campaign_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign'
            )
        );
        //form not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.campaign.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->factory->getSecurity()->isGranted('campaign:campaigns:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'campaign');
        }

        $action = $this->generateUrl('mautic_campaign_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        $eventSettings = $model->getEvents();

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                //set added/updated events
                list($modifiedEvents, $deletedEvents, $events) = $this->getSessionEvents($objectId);

                if ($valid = $this->isFormValid($form)) {
                    //make sure that at least one field is selected
                    if (empty($modifiedEvents)) {
                        //set the error
                        $form->addError(new FormError(
                            $this->get('translator')->trans('mautic.campaign.form.events.notempty', array(), 'validators')
                        ));
                        $valid = false;
                    } else {
                        $connections = $session->get('mautic.campaign.' . $objectId . '.events.canvassettings');
                        if ($connections != null) {
                            $model->setEvents($entity, $events, $connections, $deletedEvents);

                            //form is valid so process the data
                            $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                            //update canvas settings with new event IDs then save
                            $model->setCanvasSettings($entity, $connections);

                            if (!empty($deletedEvents)) {
                                $this->factory->getModel('campaign.event')->deleteEvents($entity->getEvents(), $modifiedEvents, $deletedEvents);
                            }
                        } else {
                            $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());
                        }

                        $this->addFlash('mautic.core.notice.updated', array(
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_campaign_index',
                            '%url%'       => $this->generateUrl('mautic_campaign_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ));
                    }
                } else {
                    $connections    = $session->get('mautic.campaign.' . $objectId . '.events.canvassettings');
                    $campaignEvents = $modifiedEvents;
                    if ($connections != null) {
                        $model->setCanvasSettings($entity, $connections, false, $modifiedEvents);
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                //remove fields from session
                $this->clearSessionComponents($objectId);

                $viewParameters = array(
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId()
                );

                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_campaign_action', $viewParameters),
                        'viewParameters'  =>  $viewParameters,
                        'contentTemplate' => 'MauticCampaignBundle:Campaign:view'
                    ))
                );
            } else {
                //rebuild everything to include new ids if valid
                $cleanSlate = $valid;
            }
        } else {
            $cleanSlate = true;

            //lock the entity
            $model->lockEntity($entity);

            $form->get('sessionId')->setData($objectId);
        }

        if ($cleanSlate) {
            //clean slate
            $this->clearSessionComponents($objectId);

            //load existing events into session
            $campaignEvents = array();
            $existingEvents = $entity->getEvents()->toArray();

            foreach ($existingEvents as $e) {
                $id    = $e->getId();
                $event = $e->convertToArray();
                unset($event['campaign']);
                unset($event['children']);
                unset($event['parent']);
                unset($event['log']);
                $campaignEvents[$id] = $event;
            }

            $this->setSessionEvents($objectId, $campaignEvents);

            $deletedEvents = array();
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'eventSettings'  => $eventSettings,
                'campaignEvents' => $campaignEvents,
                'deletedEvents'  => $deletedEvents,
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $form->createView()
            ),
            'contentTemplate' => 'MauticCampaignBundle:Campaign:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign',
                'route'         => $this->generateUrl('mautic_campaign_action', array(
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId())
                )
            )
        ));
    }

    /**
     * Clone an entity
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction ($objectId)
    {
        $model  = $this->factory->getModel('campaign');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('campaign:campaigns:create')) {
                return $this->accessDenied();
            }

            // Get the events that need to be duplicated as well
            $events = $entity->getEvents();

            // Clone the campaign
            /** @var \Mautic\CampaignBundle\Entity\Campaign $campaign */
            $campaign = clone $entity;
            $campaign->setIsPublished(false);

            // Clone the campaign's events
            foreach ($events as $event) {
                $campaign->removeEvent($event);

                $clone = clone $event;
                $clone->setCampaign($campaign);
                $clone->setTempId($event->getId());
                $campaign->addEvent($event->getId(), $clone);
            }

            $model->saveEntity($campaign);
            $objectId = $campaign->getId();

            $newEvents = $campaign->getEvents();
            $eventIds  = array();
            foreach ($newEvents as $n) {
                $eventIds[$n->getTempId()] = $n->getId();
            }

            // Update canvas settings with new event ids
            $canvasSettings = $campaign->getCanvasSettings();
            if (isset($canvasSettings['nodes'])) {
                foreach ($canvasSettings['nodes'] as &$node) {
                    $node['id'] = $eventIds[$node['id']];
                }
            }

            if (isset($canvasSettings['connections'])) {
                foreach ($canvasSettings['connections'] as &$c) {
                    $c['sourceId'] = $eventIds[$c['sourceId']];
                    $c['targetId'] = $eventIds[$c['targetId']];
                }
            }

            $campaign->setCanvasSettings($canvasSettings);
            $model->saveEntity($campaign);
        }

        return $this->editAction($objectId, true);
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction ($objectId)
    {
        $page      = $this->factory->getSession()->get('mautic.campaign.page', 1);
        $returnUrl = $this->generateUrl('mautic_campaign_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('campaign');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.campaign.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->isGranted('campaign:campaigns:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'campaign');
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = array(
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $identifier,
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
    public function batchDeleteAction() {
        $page        = $this->factory->getSession()->get('mautic.campaign.page', 1);
        $returnUrl   = $this->generateUrl('mautic_campaign_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->factory->getModel('campaign');
            $ids       = json_decode($this->request->query->get('ids', ''));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.campaign.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->isGranted('campaign:campaigns:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'campaign', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.campaign.notice.batch_deleted',
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
     * Clear field and events from the session
     */
    private function clearSessionComponents ($id)
    {
        $session = $this->factory->getSession();
        $session->remove('mautic.campaign.' . $id . '.events.modified');
        $session->remove('mautic.campaign.' . $id . '.events.deleted');
        $session->remove('mautic.campaign.' . $id . '.events.canvassettings');
    }

    /**
     * Get events from session
     *
     * @param mixed $id
     * @param bool  $includeDifference
     *
     * @return array
     */
    private function getSessionEvents ($id)
    {
        $session = $this->factory->getSession();

        $modifiedEvents = $session->get('mautic.campaign.' . $id . '.events.modified', array());
        $deletedEvents  = $session->get('mautic.campaign.' . $id . '.events.deleted', array());

        $events = array_diff_key($modifiedEvents, array_flip($deletedEvents));

        return array($modifiedEvents, $deletedEvents, $events);
    }

    /**
     * Set events to session
     *
     * @param $id
     * @param $events
     */
    private function setSessionEvents ($id, $events)
    {
        $session = $this->factory->getSession();

        $session->set('mautic.campaign.' . $id . '.events.modified', $events);
    }
}
