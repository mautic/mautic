<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - write merge action
//@todo - write export action

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\CoreEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;

class LeadController extends FormController
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
            'lead:leads:viewown',
            'lead:leads:viewother',
            'lead:leads:create',
            'lead:leads:editown',
            'lead:leads:editother',
            'lead:leads:deleteown',
            'lead:leads:deleteother'
        ), "RETURN_ARRAY");

        if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model   = $this->factory->getModel('lead.lead');
        $session = $this->factory->getSession();
        //set limits
        $limit = $session->get('mautic.lead.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.lead.filter', ''));
        $session->set('mautic.lead.filter', $search);

        //do some default filtering
        $orderBy    = $this->factory->getSession()->get('mautic.lead.orderby', 'l.last_active');
        $orderByDir = $this->factory->getSession()->get('mautic.lead.orderbydir', 'DESC');

        $filter      = array('string' => $search, 'force' => '');
        $translator  = $this->factory->getTranslator();
        $anonymous   = $translator->trans('mautic.lead.lead.searchcommand.isanonymous');
        $listCommand = $translator->trans('mautic.lead.lead.searchcommand.list');
        $mine        = $translator->trans('mautic.core.searchcommand.ismine');
        $indexMode   = $this->request->get('view', $session->get('mautic.lead.indexmode', 'list'));

        $session->set('mautic.lead.indexmode', $indexMode);

        // (strpos($search, "$isCommand:$anonymous") === false && strpos($search, "$listCommand:") === false)) ||
        if ($indexMode != 'list') {
            //remove anonymous leads unless requested to prevent clutter
            $filter['force'] .= " !$anonymous";
        }

        if (!$permissions['lead:leads:viewother']) {
            $filter['force'] .= " $mine";
        }

        $results = $model->getEntities(
            array(
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => $filter,
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
                'withTotalCount' => true
            ));
        $count   = $results['count'];
        unset($results['count']);

        $leads = $results['results'];
        unset($results);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $session->set('mautic.lead.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_lead_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticLeadBundle:Lead:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_lead_index',
                    'mauticContent' => 'lead'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.lead.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $listArgs = array();
        if (!$this->factory->getSecurity()->isGranted('lead:lists:viewother')) {
            $listArgs["filter"]["force"] = " $mine";
        }

        $lists = $this->factory->getModel('lead.list')->getUserLists();

        //check to see if in a single list
        $inSingleList = (substr_count($search, "$listCommand:") === 1) ? true : false;
        $list         = array();
        if ($inSingleList) {
            preg_match("/$listCommand:(.*?)(?=\s|$)/", $search, $matches);

            if (!empty($matches[1])) {
                $alias = $matches[1];
                foreach ($lists as $l) {
                    if ($alias === $l['alias']) {
                        $list = $l;
                        break;
                    }
                }
            }
        }

        // We need the EmailRepository to check if a lead is flagged as do not contact
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo = $this->factory->getModel('email')->getRepository();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'searchValue'   => $search,
                'items'         => $leads,
                'page'          => $page,
                'totalItems'    => $count,
                'limit'         => $limit,
                'permissions'   => $permissions,
                'tmpl'          => $tmpl,
                'indexMode'     => $indexMode,
                'lists'         => $lists,
                'currentList'   => $list,
                'security'      => $this->factory->getSecurity(),
                'inSingleList'  => $inSingleList,
                'noContactList' => $emailRepo->getDoNotEmailList()
            ),
            'contentTemplate' => "MauticLeadBundle:Lead:{$indexMode}.html.php",
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead',
                'route'         => $this->generateUrl('mautic_lead_index', array('page' => $page))
            )
        ));
    }

    /*
     * Quick form controller route and view
     */

    public function quickAddAction()
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model   = $this->factory->getModel('lead.lead');

        // Get the quick add form
        $action = $this->generateUrl('mautic_lead_action', array('objectAction' => 'new', 'qf' => 1));

        $fields = $this->factory->getModel('lead.field')->getEntities(array(
                'filter'         => array(
                    'force' => array(
                        array(
                            'column' => 'f.isPublished',
                            'expr'   => 'eq',
                            'value'  => true
                        ),
                        array(
                            'column' => 'f.isShortVisible',
                            'expr'   => 'eq',
                            'value'  => true
                        )
                    )
                ),
                'hydration_mode' => 'HYDRATE_ARRAY'
            ));

        $quickForm = $model->createForm($model->getEntity(), $this->get('form.factory'), $action, array('fields' => $fields, 'isShortForm' => true));

        return $this->delegateView(array(
                'viewParameters'  => array(
                    'quickForm'     => $quickForm->createView()
                ),
                'contentTemplate' => "MauticLeadBundle:Lead:quickadd.html.php",
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_lead_index',
                    'mauticContent' => 'lead',
                    'route'         => false
                )
            ));
    }

    /**
     * Loads a specific lead into the detailed panel
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction ($objectId)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->factory->getModel('lead.lead');

        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
        $lead = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.lead.page', 1);

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'lead:leads:viewown',
            'lead:leads:viewother',
            'lead:leads:create',
            'lead:leads:editown',
            'lead:leads:editother',
            'lead:leads:deleteown',
            'lead:leads:deleteother'
        ), "RETURN_ARRAY");

        if ($lead === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_lead_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticLeadBundle:Lead:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_lead_index',
                    'mauticContent' => 'lead'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.lead.lead.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        }

        if (!$this->factory->getSecurity()->hasEntityAccess(
            'lead:leads:viewown', 'lead:leads:viewother', $lead->getOwner()
        )
        ) {
            return $this->accessDenied();
        }

        $filters = $this->factory->getSession()->get('mautic.lead.' . $lead->getId() . '.timeline.filters', array(
            'search'        => '',
            'includeEvents' => array(),
            'excludeEvents' => array()
        ));

        // Trigger the TIMELINE_ON_GENERATE event to fetch the timeline events from subscribed bundles
        $dispatcher = $this->factory->getDispatcher();
        $event      = new LeadTimelineEvent($lead, $filters);
        $dispatcher->dispatch(LeadEvents::TIMELINE_ON_GENERATE, $event);

        $eventsByDate = $event->getEvents(true);
        $eventTypes   = $event->getEventTypes();

        // Get an engagement count
        $translator = $this->factory->getTranslator();
        $graphData = GraphHelper::prepareDatetimeLineGraphData(6, 'M', array($translator->trans('mautic.lead.graph.line.all_engagements'), $translator->trans('mautic.lead.graph.line.points')));
        $fromDate = $graphData['fromDate'];
        $allEngagements = array();
        $total          = 0;

        $events = array();
        foreach ($eventsByDate as $eventDate => $dateEvents) {
            $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $eventDate);
            if ($datetime > $fromDate) {
                $total++;
                $allEngagements[] = array(
                    'date' => $datetime,
                    'data' => 1
                );
            }
            $events = array_merge($events, array_reverse($dateEvents));
        }

        $graphData = GraphHelper::mergeLineGraphData($graphData, $allEngagements, 'M', 0, 'date', 'data', false, false);

        /** @var \Mautic\LeadBundle\Entity\PointChangeLogRepository $pointsLogRepository */
        $pointsLogRepository = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:PointsChangeLog');
        $pointStats          = $pointsLogRepository->getLeadPoints($fromDate, array('lead_id' => $lead->getId()));
        $engagementGraphData = GraphHelper::mergeLineGraphData($graphData, $pointStats, 'M', 1, 'date', 'data', false, false);

        // Upcoming events from Campaign Bundle
        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');

        $upcomingEvents = $leadEventLogRepository->getUpcomingEvents(array('lead' => $lead, 'scheduled' => 1, 'eventType' => 'action'));

        $fields            = $lead->getFields();
        $integrationHelper = $this->factory->getHelper('integration');
        $socialProfiles    = $integrationHelper->getUserProfiles($lead, $fields);
        $socialProfileUrls = $integrationHelper->getSocialProfileUrlRegex(false);

        $event = new IconEvent($this->factory->getSecurity());
        $this->factory->getDispatcher()->dispatch(CoreEvents::FETCH_ICONS, $event);
        $icons = $event->getIcons();

        // We need the EmailRepository to check if a lead is flagged as do not contact
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo = $this->factory->getModel('email')->getRepository();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'lead'              => $lead,
                'fields'            => $fields,
                'socialProfiles'    => $socialProfiles,
                'socialProfileUrls' => $socialProfileUrls,
                'security'          => $this->factory->getSecurity(),
                'permissions'       => $permissions,
                'events'            => $events,
                'eventTypes'        => $eventTypes,
                'eventFilters'      => $filters,
                'upcomingEvents'    => $upcomingEvents,
                'icons'             => $icons,
                'engagementData'    => $engagementGraphData,
                'noteCount'         => $this->factory->getModel('lead.note')->getNoteCount($lead, true),
                'doNotContact'      => $emailRepo->checkDoNotEmail($fields['core']['email']['value']),
                'leadNotes'         => $this->forward('MauticLeadBundle:Note:index', array(
                    'leadId'     => $lead->getId(),
                    'ignoreAjax' => 1
                ))->getContent()
            ),
            'contentTemplate' => 'MauticLeadBundle:Lead:lead.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead',
                'route'         => $this->generateUrl('mautic_lead_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $lead->getId()
                ))
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
        $model = $this->factory->getModel('lead.lead');
        $lead  = $model->getEntity();

        if (!$this->factory->getSecurity()->isGranted('lead:leads:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.lead.page', 1);

        $action = $this->generateUrl('mautic_lead_action', array('objectAction' => 'new'));
        $fields = $this->factory->getModel('lead.field')->getEntities(array(
            'force'          => array(
                array(
                    'column' => 'f.isPublished',
                    'expr'   => 'eq',
                    'value'  => true
                )
            ),
            'hydration_mode' => 'HYDRATE_ARRAY'
        ));
        $form   = $model->createForm($lead, $this->get('form.factory'), $action, array('fields' => $fields));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //get custom field values
                    $data = $this->request->request->get('lead');

                    //pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $data[$f->getName()] = $f->getData();
                    }

                    $model->setFieldValues($lead, $data, true);

                    //form is valid so process the data
                    $model->saveEntity($lead);

                    $identifier = $this->get('translator')->trans($lead->getPrimaryIdentifier());

                    $this->addFlash('mautic.core.notice.created', array(
                        '%name%'      => $identifier,
                        '%menu_link%' => 'mautic_lead_index',
                        '%url%'       => $this->generateUrl('mautic_lead_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $lead->getId()
                        ))
                    ));

                    $inQuickForm = $this->request->get('qf', false);

                    if ($inQuickForm) {
                        $viewParameters = array('page' => $page);
                        $returnUrl      = $this->generateUrl('mautic_lead_index', $viewParameters);
                        $template       = 'MauticLeadBundle:Lead:index';
                    } elseif ($form->get('buttons')->get('save')->isClicked()) {
                        $viewParameters = array(
                            'objectAction' => 'view',
                            'objectId'     => $lead->getId()
                        );
                        $returnUrl      = $this->generateUrl('mautic_lead_action', $viewParameters);
                        $template       = 'MauticLeadBundle:Lead:view';
                    } else {
                        return $this->editAction($lead->getId(), true);
                    }
                }
            } else {
                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl('mautic_lead_index', $viewParameters);
                $template       = 'MauticLeadBundle:Lead:index';
            }

            if ($cancelled || $valid) { //cancelled or success
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_lead_index',
                        'mauticContent' => 'lead',
                        'closeModal'    => 1, //just in case in quick form
                    )
                ));
            }
        } else {
            //set the default owner to the currently logged in user
            $currentUser = $this->get('security.context')->getToken()->getUser();
            $form->get('owner')->setData($currentUser);
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'   => $form->createView(),
                'lead'   => $lead,
                'fields' => $model->organizeFieldsByGroup($fields)
            ),
            'contentTemplate' => 'MauticLeadBundle:Lead:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead',
                'route'         => $this->generateUrl('mautic_lead_action', array(
                    'objectAction' => 'new'
                ))
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
        $model = $this->factory->getModel('lead.lead');
        $lead  = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.lead.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_lead_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticLeadBundle:Lead:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead'
            )
        );
        //lead not found
        if ($lead === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.lead.lead.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'lead:leads:editown', 'lead:leads:editother', $lead->getOwner()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($lead)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $lead, 'lead.lead');
        }

        $action = $this->generateUrl('mautic_lead_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $fields = $this->factory->getModel('lead.field')->getEntities(array(
            'force'          => array(
                array(
                    'column' => 'f.isPublished',
                    'expr'   => 'eq',
                    'value'  => true
                )
            ),
            'hydration_mode' => 'HYDRATE_ARRAY'
        ));
        $form   = $model->createForm($lead, $this->get('form.factory'), $action, array('fields' => $fields));

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $data = $this->request->request->get('lead');

                    //pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $name = $f->getName();
                        if (strpos($name, 'field_') === 0) {
                            $data[$name] = $f->getData();
                        }
                    }

                    $model->setFieldValues($lead, $data, true);
                    //form is valid so process the data
                    $model->saveEntity($lead, $form->get('buttons')->get('save')->isClicked());

                    $identifier = $this->get('translator')->trans($lead->getPrimaryIdentifier());

                    $this->addFlash('mautic.core.notice.updated', array(
                        '%name%'      => $identifier,
                        '%menu_link%' => 'mautic_lead_index',
                        '%url%'       => $this->generateUrl('mautic_lead_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $lead->getId()
                        ))
                    ));
                }
            } else {
                //unlock the entity
                $model->unlockEntity($lead);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                $viewParameters = array(
                    'objectAction' => 'view',
                    'objectId'     => $lead->getId()
                );

                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_lead_action', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'MauticLeadBundle:Lead:view'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($lead);
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'   => $form->createView(),
                'lead'   => $lead,
                'fields' => $lead->getFields() //pass in the lead fields as they are already organized by ['group']['alias']
            ),
            'contentTemplate' => 'MauticLeadBundle:Lead:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead',
                'route'         => $this->generateUrl('mautic_lead_action', array(
                        'objectAction' => 'edit',
                        'objectId'     => $lead->getId())
                )
            )
        ));
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
        $page      = $this->factory->getSession()->get('mautic.lead.page', 1);
        $returnUrl = $this->generateUrl('mautic_lead_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticLeadBundle:Lead:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('lead.lead');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.lead.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                'lead:leads:deleteown', 'lead:leads:deleteother', $entity->getOwner()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'lead.lead');
            } else {
                $model->deleteEntity($entity);

                $identifier = $this->get('translator')->trans($entity->getPrimaryIdentifier());
                $flashes[]  = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => array(
                        '%name%' => $identifier,
                        '%id%'   => $objectId
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
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction ()
    {
        $page      = $this->factory->getSession()->get('mautic.lead.page', 1);
        $returnUrl = $this->generateUrl('mautic_lead_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticLeadBundle:Lead:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->factory->getModel('lead');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.lead.lead.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                    'lead:leads:deleteown', 'lead:leads:deleteother', $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'lead', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.lead.lead.notice.batch_deleted',
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
     * Add/remove lead from a list
     *
     * @param $objectId
     */
    public function listAction ($objectId)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->factory->getModel('lead');
        $lead  = $model->getEntity($objectId);

        if ($lead != null && $this->factory->getSecurity()->hasEntityAccess(
                'lead:leads:editown', 'lead:leads:editother', $lead->getOwner()
            )
        ) {
            /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
            $listModel = $this->factory->getModel('lead.list');
            $lists     = $listModel->getUserLists();

            // Get a list of lists for the lead
            $leadsLists = $model->getLists($lead, true, true);
        } else {
            $lists = $leadsLists = array();
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'lists'      => $lists,
                'leadsLists' => $leadsLists,
                'lead'       => $lead
            ),
            'contentTemplate' => 'MauticLeadBundle:LeadLists:index.html.php'
        ));
    }


    /**
     * Add/remove lead from a campaign
     *
     * @param $objectId
     */
    public function campaignAction ($objectId)
    {
        $model = $this->factory->getModel('lead');
        $lead  = $model->getEntity($objectId);

        if ($lead != null && $this->factory->getSecurity()->hasEntityAccess(
                'lead:leads:editown', 'lead:leads:editother', $lead->getOwner()
            )
        ) {
            /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
            $campaignModel  = $this->factory->getModel('campaign');
            $campaigns      = $campaignModel->getPublishedCampaigns(true);
            $leadsCampaigns = $campaignModel->getLeadCampaigns($lead, true);

            foreach ($campaigns as $c) {
                $campaigns[$c['id']]['inCampaign'] = (isset($leadsCampaigns[$c['id']])) ? true : false;
            }
        } else {
            $campaigns = array();
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'campaigns' => $campaigns,
                'lead'      => $lead
            ),
            'contentTemplate' => 'MauticLeadBundle:LeadCampaigns:index.html.php'
        ));
    }

    /**
     * @param $objectId
     */
    public function importAction ($objectId = 0, $ignorePost = false)
    {
        //Auto detect line endings for the file to work around MS DOS vs Unix new line characters
        ini_set('auto_detect_line_endings', true);

        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model   = $this->factory->getModel('lead');
        $session = $this->factory->getSession();

        if (!$this->factory->getSecurity()->isGranted('lead:leads:create')) {
            return $this->accessDenied();
        }

        // Move the file to cache and rename it
        $forceStop = $this->request->get('cancel', false);
        $step      = ($forceStop) ? 1 : $session->get('mautic.lead.import.step', 1);
        $cacheDir  = $this->factory->getSystemPath('cache', true);
        $username  = $this->factory->getUser()->getUsername();
        $fileName  = $username . '_leadimport.csv';
        $fullPath  = $cacheDir . '/' . $fileName;
        $complete  = false;
        if (!file_exists($fullPath)) {
            // Force step one if the file doesn't exist
            $step = 1;
            $session->set('mautic.lead.import.step', 1);
        }

        $progress = $session->get('mautic.lead.import.progress', array(0, 0));
        $stats    = $session->get('mautic.lead.import.stats', array('merged' => 0, 'created' => 0, 'ignored' => 0));
        $action   = $this->generateUrl('mautic_lead_action', array('objectAction' => 'import'));

        switch ($step) {
            case 1:
                // Upload file

                if ($forceStop) {
                    $this->resetImport($fullPath);
                }

                $session->set('mautic.lead.import.headers', array());
                $form = $this->get('form.factory')->create('lead_import', array(), array('action' => $action));
                break;
            case 2:
                // Match fields

                /** @var \Mautic\LeadBundle\Model\FieldModel $addonModel */
                $fieldModel = $this->factory->getModel('lead.field');

                $leadFields   = $fieldModel->getFieldList(false, false);
                $importFields = $session->get('mautic.lead.import.importfields', array());

                $form = $this->get('form.factory')->create('lead_field_import', array(), array(
                    'action'        => $action,
                    'lead_fields'   => $leadFields,
                    'import_fields' => $importFields
                ));
                break;
            case 3:
                // Just show the progress form
                $session->set('mautic.lead.import.step', 4);
                break;

            case 4:
                ignore_user_abort(true);

                $inProgress = $session->get('mautic.lead.import.inprogress', false);
                $checks     = $session->get('mautic.lead.import.progresschecks', 1);
                if (!$inProgress || $checks > 5) {
                    $session->set('mautic.lead.import.inprogress', true);
                    $session->set('mautic.lead.import.progresschecks', 1);

                    // Batch process
                    $defaultOwner = $session->get('mautic.lead.import.defaultowner', null);
                    $headers      = $session->get('mautic.lead.import.headers', array());
                    $importFields = $session->get('mautic.lead.import.fields', array());

                    $file      = new \SplFileObject($fullPath);
                    if ($file !== false) {
                        $lineNumber = $progress[0];

                        if ($lineNumber > 0) {
                            $file->seek($lineNumber);
                        }

                        $config    = $session->get('mautic.lead.import.config');
                        $batchSize = $config['batchlimit'];

                        while ($batchSize && !$file->eof()) {
                            $data = $file->fgetcsv($config['delimiter'], $config['enclosure'], $config['escape']);
                            if ($lineNumber === 0) {
                                $lineNumber++;
                                continue;
                            }

                            // Increase progress count
                            $progress[0]++;

                            // Decrease batch count
                            $batchSize--;

                            if (is_array($data) && count($headers) === count($data)) {

                                $data = array_combine($headers, $data);

                                if (empty($data)) {
                                    $stats['ignored']++;
                                } else {
                                    $merged = $model->importLead($importFields, $data, $defaultOwner);

                                    if ($merged) {
                                        $stats['merged']++;
                                    } else {
                                        $stats['created']++;
                                    }
                                }
                            }
                        }

                        $session->set('mautic.lead.import.stats', $stats);
                    }

                    // Close the file
                    $file = null;

                    // Clear in progress
                    if ($progress[0] >= $progress[1]) {
                        $progress[0] = $progress[1];
                        $this->resetImport($fullPath);
                        $complete = true;

                    } else {
                        $complete = false;
                        $session->set('mautic.lead.import.inprogress', false);
                        $session->set('mautic.lead.import.progress', $progress);
                    }

                    break;
                } else {
                    $checks++;
                    $session->set('mautic.lead.import.progresschecks', $checks);
                }
        }

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                $valid = $this->isFormValid($form);
                switch ($step) {
                    case 1:
                        if ($valid) {
                            if (file_exists($fullPath)) {
                                unlink($fullPath);
                            }

                            $fileData = $form['file']->getData();
                            if (!empty($fileData)) {
                                try {
                                    $fileData->move($cacheDir, $fileName);

                                    $file = new \SplFileObject($fullPath);

                                    $config = $form->getData();
                                    unset($config['file']);
                                    unset($config['start']);

                                    foreach ($config as $key => &$c) {
                                        $c = htmlspecialchars_decode($c);

                                        if ($key == 'batchlimit') {
                                            $c = (int) $c;
                                        }
                                    }

                                    $session->set('mautic.lead.import.config', $config);

                                    if ($file !== false) {
                                        // Get the headers for matching
                                        $headers = $file->fgetcsv($config['delimiter'], $config['enclosure'], $config['escape']);

                                        // Get the number of lines so we can track progress
                                        $file->seek(PHP_INT_MAX);
                                        $linecount = $file->key();

                                        if (!empty($headers) && is_array($headers)) {
                                            $session->set('mautic.lead.import.headers', $headers);
                                            sort($headers);
                                            $headers = array_combine($headers, $headers);
                                            $session->set('mautic.lead.import.step', 2);
                                            $session->set('mautic.lead.import.importfields', $headers);
                                            $session->set('mautic.lead.import.progress', array(0, $linecount));

                                            return $this->importAction(0, true);
                                        }
                                    }
                                } catch (\Exception $e) {
                                }
                            }

                            $form->addError(new FormError(
                                $this->factory->getTranslator()->trans('mautic.lead.import.filenotreadable', array(), 'validators')
                            ));
                        }
                        break;
                    case 2:
                        // Save matched fields
                        $matchedFields = $form->getData();

                        if (empty($matchedFields)) {
                            $this->resetImport($fullPath);

                            return $this->importAction(0, true);
                        }

                        $owner = $matchedFields['owner'];
                        unset($matchedFields['owner']);

                        foreach ($matchedFields as $k => $f) {
                            if (empty($f)) {
                                unset($matchedFields[$k]);
                            }
                        }

                        if (empty($matchedFields)) {
                            $form->addError(new FormError(
                                $this->factory->getTranslator()->trans('mautic.lead.import.matchfields', array(), 'validators')
                            ));
                        } else {

                            $defaultOwner = ($owner) ? $owner->getId() : null;
                            $session->set('mautic.lead.import.fields', $matchedFields);
                            $session->set('mautic.lead.import.defaultowner', $defaultOwner);
                            $session->set('mautic.lead.import.step', 3);

                            return $this->importAction(0, true);
                        }
                        break;

                    default:
                        // Done or something wrong

                        $this->resetImport($fullPath);

                        break;
                }
            } else {
                $this->resetImport($fullPath);

                return $this->importAction(0, true);
            }
        }

        if ($step === 1 || $step === 2) {
            $contentTemplate = 'MauticLeadBundle:Import:form.html.php';
            $viewParameters  = array('form' => $form->createView());
        } else {
            $contentTemplate = 'MauticLeadBundle:Import:progress.html.php';
            $viewParameters  = array(
                'progress' => $progress,
                'stats'    => $stats,
                'complete' => $complete
            );
        }

        if (!$complete && $this->request->query->has('importbatch')) {
            // Ajax request to batch process so just return ajax response unless complete

            return new JsonResponse(array('success' => 1, 'ignore_wdt' => 1));
        } else {
            return $this->delegateView(array(
                'viewParameters'  => $viewParameters,
                'contentTemplate' => $contentTemplate,
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_lead_index',
                    'mauticContent' => 'leadImport',
                    'route'         => $this->generateUrl('mautic_lead_action', array(
                            'objectAction' => 'import'
                        )
                    ),
                    'step'          => $step,
                    'progress'      => $progress
                )
            ));
        }
    }

    /**
     * @param $filepath
     */
    private function resetImport ($filepath)
    {
        $session = $this->factory->getSession();
        $session->set('mautic.lead.import.stats', array('merged' => 0, 'created' => 0, 'ignored' => 0));
        $session->set('mautic.lead.import.headers', array());
        $session->set('mautic.lead.import.step', 1);
        $session->set('mautic.lead.import.progress', array(0,0));
        $session->set('mautic.lead.import.fields', array());
        $session->set('mautic.lead.import.defaultowner', null);
        $session->set('mautic.lead.import.inprogress', false);
        $session->set('mautic.lead.import.importfields', array());
        unlink($filepath);
    }
}
