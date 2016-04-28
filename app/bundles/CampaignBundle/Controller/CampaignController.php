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
use Mautic\CampaignBundle\Entity\Campaign;
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
    public function indexAction($page = 1)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model   = $this->factory->getModel('campaign');
        $session = $this->factory->getSession();

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(
            array(
                'campaign:campaigns:view',
                'campaign:campaigns:create',
                'campaign:campaigns:edit',
                'campaign:campaigns:delete',
                'campaign:campaigns:publish'

            ),
            "RETURN_ARRAY"
        );

        if (!$permissions['campaign:campaigns:view']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $session->get('mautic.campaign.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.campaign.filter', ''));
        $session->set('mautic.campaign.filter', $search);

        $filter     = array('string' => $search, 'force' => array());

        $currentFilters = $session->get('mautic.campaign.list_filters', array());
        $updatedFilters = $this->request->get('filters', false);

        $sourceLists = $model->getSourceLists();
        $listFilters = array(
            'filters'      => array(
                'multiple' => true,
                'groups'   => array(
                    'mautic.campaign.leadsource.form' => array(
                        'options' => $sourceLists['forms'],
                        'prefix'  => 'form'
                    ),
                    'mautic.campaign.leadsource.list' => array(
                        'options' => $sourceLists['lists'],
                        'prefix'  => 'list'
                    )
                )
            )
        );

        if ($updatedFilters) {
            // Filters have been updated

            // Parse the selected values
            $newFilters     = array();
            $updatedFilters = json_decode($updatedFilters, true);

            if ($updatedFilters) {
                foreach ($updatedFilters as $updatedFilter) {
                    list($clmn, $fltr) = explode(':', $updatedFilter);

                    $newFilters[$clmn][] = $fltr;
                }

                $currentFilters = $newFilters;
            } else {
                $currentFilters = array();
            }
        }
        $session->set('mautic.campaign.list_filters', $currentFilters);

        $joinLists = $joinForms = false;
        if (!empty($currentFilters)) {
            $listIds = $catIds = array();
            foreach ($currentFilters as $type => $typeFilters) {
                $listFilters['filters']['groups']['mautic.campaign.leadsource.' . $type]['values'] = $typeFilters;

                foreach ($typeFilters as $fltr) {
                    if ($type == 'list') {
                        $listIds[] = (int) $fltr;
                    } else {
                        $formIds[] = (int) $fltr;
                    }
                }
            }

            if (!empty($listIds)) {
                $joinLists = true;
                $filter['force'][] = array('column' => 'l.id', 'expr' => 'in', 'value' => $listIds);
            }

            if (!empty($formIds)) {
                $joinForms = true;
                $filter['force'][] = array('column' => 'f.id', 'expr' => 'in', 'value' => $formIds);
            }
        }

        $orderBy    = $session->get('mautic.campaign.orderby', 'c.name');
        $orderByDir = $session->get('mautic.campaign.orderbydir', 'ASC');

        $campaigns = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
                'joinLists'  => $joinLists,
                'joinForms'  => $joinForms
            )
        );

        $count = count($campaigns);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $session->set('mautic.campaign.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_campaign_index', array('page' => $lastPage));

            return $this->postActionRedirect(
                array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $lastPage),
                    'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_campaign_index',
                        'mauticContent' => 'campaign'
                    )
                )
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.campaign.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'searchValue' => $search,
                    'items'       => $campaigns,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'tmpl'        => $tmpl,
                    'filters'     => $listFilters
                ),
                'contentTemplate' => 'MauticCampaignBundle:Campaign:list.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign',
                    'route'         => $this->generateUrl('mautic_campaign_index', array('page' => $page))
                )
            )
        );
    }

    /**
     * View a specific campaign
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        $tmpl = $this->request->get('tmpl', 'index');

        if ($tmpl == 'campaignleads') {
            //forward to leadsAction
            $page  = $this->factory->getSession()->get('mautic.campaign.lead.page', 1);
            $query = array("ignoreAjax" => true, 'request' => $this->request);

            return $this->forward('MauticCampaignBundle:Campaign:leads', array('objectId' => $objectId, 'page' => $page, $query));
        }

        $page = $this->factory->getSession()->get('mautic.campaign.page', 1);

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model     = $this->factory->getModel('campaign');

        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $pageModel = $this->factory->getModel('page');

        $security  = $this->factory->getSecurity();
        $entity    = $model->getEntity($objectId);

        $permissions = $security->isGranted(
            array(
                'campaign:campaigns:view',
                'campaign:campaigns:create',
                'campaign:campaigns:edit',
                'campaign:campaigns:delete',
                'campaign:campaigns:publish'
            ),
            "RETURN_ARRAY"
        );


        if ($entity === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_campaign_index', array('page' => $page));

            return $this->postActionRedirect(
                array(
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
                )
            );
        } elseif (!$permissions['campaign:campaigns:view']) {
            return $this->accessDenied();
        }

        $campaignLeadRepo = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:Lead');
        $eventLogRepo     = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');
        $events           = $model->getEventRepository()->getCampaignEvents($entity->getId());
        $leadCount        = $model->getRepository()->getCampaignLeadCount($entity->getId());

        $campaignLogCounts = $eventLogRepo->getCampaignLogCounts($entity->getId(), true);

        foreach ($events as &$event) {
            $event['logCount'] = (isset($campaignLogCounts[$event['id']])) ? (int) $campaignLogCounts[$event['id']] : 0;
            $event['percent']  = ($leadCount) ? round($event['logCount'] / $leadCount * 100) : 0;
        }

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject('campaign', $objectId, $entity->getDateAdded());

        // Hit count per day for last 30 days
        $hits = $pageModel->getHitsBarChartData(null, new \DateTime('-30 days'), new \DateTime, null, array('source_id' => $objectId, 'source' => 'campaign'));

        // Sent emails stats
        $emailsSent = $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat')->getIgnoredReadFailed(
            null,
            array('source_id' => $entity->getId(), 'source' => 'campaign')
        );

        // Lead count stats
        $leadStats = $model->getLeadsAddedLineChartData(null, new \DateTime('-30 days'), new \DateTime, null, array('campaign_id' => $objectId));

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'campaign'      => $entity,
                    'permissions'   => $permissions,
                    'security'      => $security,
                    'logs'          => $logs,
                    'hits'          => $hits,
                    'emailsSent'    => $emailsSent,
                    'leadStats'     => $leadStats,
                    'events'        => $events,
                    'campaignLeads' => $this->forward(
                        'MauticCampaignBundle:Campaign:leads',
                        array(
                            'objectId'   => $entity->getId(),
                            'page'       => $this->factory->getSession()->get('mautic.campaign.lead.page', 1),
                            'ignoreAjax' => true
                        )
                    )->getContent()
                ),
                'contentTemplate' => 'MauticCampaignBundle:Campaign:details.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign',
                    'route'         => $this->generateUrl(
                        'mautic_campaign_action',
                        array(
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId()
                        )
                    )
                )
            )
        );
    }

    /**
     * @param     $objectId
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function leadsAction($objectId, $page = 1)
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
        $orderBy    = $this->factory->getSession()->get('mautic.campaign.lead.orderby', 'l.id');
        $orderByDir = $this->factory->getSession()->get('mautic.campaign.lead.orderbydir', 'DESC');

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
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $this->factory->getSession()->set('mautic.campaign.lead.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_campaign_leads', array('objectId' => $objectId, 'page' => $lastPage));

            return $this->postActionRedirect(
                array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $lastPage, 'objectId' => $objectId),
                    'contentTemplate' => 'MauticLeadBundle:Lead:grid.html.php',
                    'passthroughVars' => array(
                        'mauticContent' => 'campaignLeads'
                    )
                )
            );
        }

        $triggerModel = $this->factory->getModel('point.trigger');
        foreach ($leads['results'] as &$l) {
            $l['color'] = $triggerModel->getColorForLeadPoints($l['points']);
        }

        return $this->delegateView(
            array(
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
            )
        );
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
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

        //set added/updated events
        list($modifiedEvents, $deletedEvents, $campaignEvents) = $this->getSessionEvents($sessionId);

        //set added/updated sources
        list($addedSources, $deletedSources, $currentSources) = $this->getSessionSources($sessionId);

        //setup the form
        $action = $this->generateUrl('mautic_campaign_action', array('objectAction' => 'new'));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        //get event settings
        $eventSettings = $model->getEvents();

        $campaignSources = array();

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //make sure that at least one action is selected
                    if (empty($campaignEvents)) {
                        //set the error
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.campaign.form.events.notempty', array(), 'validators')
                            )
                        );
                        $valid = false;
                    } elseif (empty($currentSources['lists']) && empty($currentSources['forms'])) {
                        //set the error
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.campaign.form.sources.notempty', array(), 'validators')
                            )
                        );
                        $valid = false;
                    } else {
                        // Set lead sources
                        $model->setLeadSources($entity, $addedSources, $deletedSources);

                        $connections = $session->get('mautic.campaign.'.$sessionId.'.events.canvassettings');
                        // Build and set Event entities
                        $model->setEvents($entity, $campaignEvents, $connections, $deletedEvents, $currentSources);

                        // Persist to the database before building connection so that IDs are available
                        $model->saveEntity($entity);

                        // Update canvas settings with new event IDs then save
                        $model->setCanvasSettings($entity, $connections);

                        $this->addFlash(
                            'mautic.core.notice.created',
                            array(
                                '%name%'      => $entity->getName(),
                                '%menu_link%' => 'mautic_campaign_index',
                                '%url%'       => $this->generateUrl(
                                    'mautic_campaign_action',
                                    array(
                                        'objectAction' => 'edit',
                                        'objectId'     => $entity->getId()
                                    )
                                )
                            )
                        );

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
                }

                if (!$valid) {
                    $connections = $session->get('mautic.campaign.'.$sessionId.'.events.canvassettings');
                    $model->setCanvasSettings($entity, $connections, false, $modifiedEvents);

                    foreach ($currentSources as $type => $sources) {
                        if (!empty($sources)) {
                            $sourceList             = $model->getSourceLists($type);
                            $campaignSources[$type] = array(
                                'sourceType' => $type,
                                'campaignId' => $sessionId,
                                'names'      => implode(', ', array_intersect_key($sourceList, array_flip($sources)))
                            );
                        }
                    }
                }
            } else {
                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl('mautic_campaign_index', $viewParameters);
                $template       = 'MauticCampaignBundle:Campaign:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                //clear temporary fields
                $this->clearSessionComponents($sessionId);

                return $this->postActionRedirect(
                    array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => array(
                            'activeLink'    => '#mautic_campaign_index',
                            'mauticContent' => 'campaign'
                        )
                    )
                );
            }
        } else {
            //clear out existing fields in case the form was refreshed, browser closed, etc
            $this->clearSessionComponents($sessionId);
            $modifiedEvents = $deletedEvents = $campaignSources = array();

            $form->get('sessionId')->setData($sessionId);
        }

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'eventSettings'   => $eventSettings,

                    'campaignEvents'  => $modifiedEvents,
                    'campaignSources' => $campaignSources,
                    'deletedEvents'   => $deletedEvents,
                    'tmpl'            => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity'          => $entity,
                    'form'            => $form->createView()
                ),
                'contentTemplate' => 'MauticCampaignBundle:Campaign:form.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign',
                    'route'         => $this->generateUrl(
                        'mautic_campaign_action',
                        array(
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                            'objectId'     => $entity->getId()
                        )
                    )
                )
            )
        );
    }

    /**
     * Generates edit form and processes post data
     *
     * @param integer|string $objectId
     * @param boolean        $ignorePost
     * @param Campaign       $clonedEntity
     * @param array          $currentSources
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction($objectId, $ignorePost = false, Campaign $clonedEntity = null, array $currentSources = null)
    {

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model      = $this->factory->getModel('campaign');
        $formData   = $this->request->request->get('campaign');
        $sessionId  = isset($formData['sessionId']) ? $formData['sessionId'] : null;
        $session    = $this->factory->getSession();

        $isClone = false;
        if ($clonedEntity instanceof Campaign) {
            $entity  = $clonedEntity;
            $isClone = true;
        } else {
            $entity = $model->getEntity($objectId);

            // Process submit of cloned campaign
            if ($entity == null && $objectId == $sessionId) {
                $entity  = $model->getEntity();
                $isClone = true;
            }
        }

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
                array_merge(
                    $postActionVars,
                    array(
                        'flashes' => array(
                            array(
                                'type'    => 'error',
                                'msg'     => 'mautic.campaign.error.notfound',
                                'msgVars' => array('%id%' => $objectId)
                            )
                        )
                    )
                )
            );
        } elseif (!$this->factory->getSecurity()->isGranted('campaign:campaigns:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'campaign');
        }

        $action = $this->generateUrl('mautic_campaign_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        // Get event settings
        $eventSettings = $model->getEvents();

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {

                //set added/updated events
                list($modifiedEvents, $deletedEvents, $campaignEvents) = $this->getSessionEvents($objectId);

                //set added/updated sources
                list($addedSources, $deletedSources, $currentSources) = $this->getSessionSources($objectId, $isClone);

                if ($valid = $this->isFormValid($form)) {
                    //make sure that at least one field is selected
                    if (empty($campaignEvents)) {
                        //set the error
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.campaign.form.events.notempty', array(), 'validators')
                            )
                        );
                        $valid = false;
                    } elseif (empty($currentSources['lists']) && empty($currentSources['forms'])) {
                        //set the error
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.campaign.form.sources.notempty', array(), 'validators')
                            )
                        );
                        $valid = false;
                    } else {
                        // If this is a clone, we need to save the entity first to properly build the events, sources and canvas settings
                        if ($isClone) {
                            $model->getRepository()->saveEntity($entity);
                        }

                        //set sources
                        $model->setLeadSources($entity, $addedSources, $deletedSources);

                        //set events and connections
                        $connections = $session->get('mautic.campaign.'.$objectId.'.events.canvassettings');

                        if ($connections != null) {
                            // Build and persist events
                            $model->setEvents($entity, $campaignEvents, $connections, $deletedEvents, $currentSources);

                            // Update canvas settings with new event IDs if applicable then save
                            $model->setCanvasSettings($entity, $connections);

                            if (!empty($deletedEvents)) {
                                $this->factory->getModel('campaign.event')->deleteEvents($entity->getEvents(), $modifiedEvents, $deletedEvents);
                            }
                        }

                        $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                        $this->addFlash(
                            'mautic.core.notice.updated',
                            array(
                                '%name%'      => $entity->getName(),
                                '%menu_link%' => 'mautic_campaign_index',
                                '%url%'       => $this->generateUrl(
                                    'mautic_campaign_action',
                                    array(
                                        'objectAction' => 'edit',
                                        'objectId'     => $entity->getId()
                                    )
                                )
                            )
                        );
                    }
                } else {
                    $connections    = $session->get('mautic.campaign.'.$objectId.'.events.canvassettings');
                    $campaignEvents = $modifiedEvents;
                    if ($connections != null) {
                        $model->setCanvasSettings($entity, $connections, false, $modifiedEvents);
                    }
                }
            } elseif (!$isClone) {
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

                if (!$isClone) {
                    $postActionVars = array_merge(
                        $postActionVars,
                        array(
                            'returnUrl'       => $this->generateUrl('mautic_campaign_action', $viewParameters),
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => 'MauticCampaignBundle:Campaign:view'
                        )
                    );
                } // else redirect to index since there is no view page for the cancelled clone

                return $this->postActionRedirect($postActionVars);
            } else {
                //rebuild everything to include new ids if valid
                $cleanSlate = $valid;
            }
        } else {
            $cleanSlate = true;

            //lock the entity
            if (!$isClone) {
                $model->lockEntity($entity);
            }

            $form->get('sessionId')->setData($objectId);
        }

        if ($cleanSlate) {
            if (!$isClone) {
                //clean slate
                $this->clearSessionComponents($objectId);
            }

            //load existing events into session
            $campaignEvents = array();
            $existingEvents = $entity->getEvents()->toArray();

            foreach ($existingEvents as $e) {
                $event = $e->convertToArray();

                if ($isClone) {
                    $id          = $e->getTempId();
                    $event['id'] = $id;
                } else {
                    $id = $e->getId();
                }

                unset($event['campaign']);
                unset($event['children']);
                unset($event['parent']);
                unset($event['log']);
                $campaignEvents[$id] = $event;
            }

            $this->setSessionEvents($objectId, $campaignEvents);

            $deletedEvents = array();

            if (!$currentSources) {
                //load sources to session
                $currentSources = $model->getLeadSources($objectId);
            }

            $this->setSessionSources($objectId, $currentSources);
        }

        $campaignSources = array();
        if (isset($currentSources) && is_array($currentSources)) {
            foreach ($currentSources as $type => $sources) {
                if (!empty($sources)) {
                    $sourceList             = $model->getSourceLists($type);
                    $campaignSources[$type] = array(
                        'sourceType' => $type,
                        'campaignId' => $objectId,
                        'names'      => implode(', ', array_intersect_key($sourceList, $sources))
                    );
                }
            }
        }

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'eventSettings'   => $eventSettings,
                    'campaignEvents'  => $campaignEvents,
                    'campaignSources' => $campaignSources,
                    'deletedEvents'   => $deletedEvents,
                    'tmpl'            => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity'          => $entity,
                    'form'            => $form->createView()
                ),
                'contentTemplate' => 'MauticCampaignBundle:Campaign:form.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign',
                    'route'         => $this->generateUrl(
                        'mautic_campaign_action',
                        array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        )
                    )
                )
            )
        );
    }

    /**
     * Clone an entity
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        $model    = $this->factory->getModel('campaign');
        $campaign = $model->getEntity($objectId);

        // Generate temporary ID
        $tempId = sha1(uniqid(mt_rand(), true));

        // load sources to session
        $currentSources = $model->getLeadSources($objectId);

        if ($campaign != null) {
            if (!$this->factory->getSecurity()->isGranted('campaign:campaigns:create')) {
                return $this->accessDenied();
            }

            // Get the events that need to be duplicated as well
            $events = $campaign->getEvents();

            // Clone the campaign
            /** @var \Mautic\CampaignBundle\Entity\Campaign $campaign */
            $campaign = clone $campaign;
            $campaign->setIsPublished(false);

            // Clone the campaign's events
            foreach ($events as $event) {
                $tempEventId = 'new' . $event->getId();

                $clone = clone $event;
                $clone->setCampaign($campaign);
                $clone->setTempId($tempEventId);

                // Just wipe out the parent as it'll be generated when the cloned entity is saved
                $clone->setParent(null);

                $campaign->addEvent($tempEventId, $clone);
            }

            // Update canvas settings with new event ids
            $canvasSettings = $campaign->getCanvasSettings();
            if (isset($canvasSettings['nodes'])) {
                foreach ($canvasSettings['nodes'] as &$node) {
                    // Only events and not lead sources
                    if (is_numeric($node['id'])) {
                        $node['id'] = 'new'.$node['id'];
                    }
                }
            }

            if (isset($canvasSettings['connections'])) {
                foreach ($canvasSettings['connections'] as &$c) {
                    // Only events and not lead sources
                    if (is_numeric($c['sourceId'])) {
                        $c['sourceId'] = 'new'.$c['sourceId'];
                    }

                    // Only events and not lead sources
                    if (is_numeric($c['targetId'])) {
                        $c['targetId'] = 'new'.$c['targetId'];
                    }
                }
            }

            $campaign->setCanvasSettings($canvasSettings);

            // Set the canvas settings into session to simulate edit
            $this->get('session')->set('mautic.campaign.'.$tempId.'.events.canvassettings', $canvasSettings);
        }

        return $this->editAction($tempId, true, $campaign, $currentSources);
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
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
            array_merge(
                $postActionVars,
                array(
                    'flashes' => $flashes
                )
            )
        );
    }

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
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
                    'type'    => 'notice',
                    'msg'     => 'mautic.campaign.notice.batch_deleted',
                    'msgVars' => array(
                        '%count%' => count($entities)
                    )
                );
            }
        } //else don't do anything

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
     * Clear field and events from the session
     *
     * @param $id
     */
    private function clearSessionComponents($id)
    {
        $session = $this->factory->getSession();
        $session->remove('mautic.campaign.'.$id.'.events.modified');
        $session->remove('mautic.campaign.'.$id.'.events.deleted');
        $session->remove('mautic.campaign.'.$id.'.events.canvassettings');
        $session->remove('mautic.campaign.'.$id.'.leadsources.modified');
        $session->remove('mautic.campaign.'.$id.'.leadsources.deleted');
    }

    /**
     * Get events from session
     *
     * @param $id
     *
     * @return array
     */
    private function getSessionEvents($id)
    {
        $session = $this->factory->getSession();

        $modifiedEvents = $session->get('mautic.campaign.'.$id.'.events.modified', array());
        $deletedEvents  = $session->get('mautic.campaign.'.$id.'.events.deleted', array());

        $events = array_diff_key($modifiedEvents, array_flip($deletedEvents));

        return array($modifiedEvents, $deletedEvents, $events);
    }

    /**
     * Set events to session
     *
     * @param $id
     * @param $events
     */
    private function setSessionEvents($id, $events)
    {
        $session = $this->factory->getSession();

        $session->set('mautic.campaign.'.$id.'.events.modified', $events);
    }

    /**
     * Get events from session
     *
     * @param $id
     * @param $isClone
     * @return array
     */
    private function getSessionSources($id, $isClone = false)
    {
        $session = $this->factory->getSession();

        $currentSources  = $session->get('mautic.campaign.'.$id.'.leadsources.current', array());
        $modifiedSources = $session->get('mautic.campaign.'.$id.'.leadsources.modified', array());

        if ($currentSources === $modifiedSources) {
            if ($isClone) {
                // Clone hasn't saved the sources yet so return the current list as added
                return array($currentSources, array(), $currentSources);
            } else {

                return array(array(), array(), $currentSources);
            }
        }

        // Deleted sources
        $deletedSources = array();
        foreach ($currentSources as $type => $sources) {
            if (isset($modifiedSources[$type])) {
                $deletedSources[$type] = array_diff($sources, $modifiedSources[$type]);
            } else {
                $deletedSources[$type] = $sources;
            }
        }

        // Added sources
        $addedSources = array();
        foreach ($modifiedSources as $type => $sources) {
            if (isset($currentSources[$type])) {
                $addedSources[$type] = array_diff($sources, $currentSources[$type]);
            } else {
                $addedSources[$type] = $sources;
            }
        }

        return array($addedSources, $deletedSources, $modifiedSources);
    }

    /**
     * Set sources to session
     *
     * @param $id
     * @param $sources
     */
    private function setSessionSources($id, $sources)
    {
        $session = $this->factory->getSession();
        foreach ($sources as $type => &$typeSources) {
            if (!empty($typeSources)) {
                $typeSources = array_keys($typeSources);
                $typeSources = array_combine($typeSources, $typeSources);
            }
        }

        $session->set('mautic.campaign.'.$id.'.leadsources.current', $sources);
        $session->set('mautic.campaign.'.$id.'.leadsources.modified', $sources);
    }
}
