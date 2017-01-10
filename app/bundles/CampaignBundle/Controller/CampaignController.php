<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CampaignController extends FormController
{
    use EntityContactsTrait;

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model   = $this->getModel('campaign');
        $session = $this->get('session');

        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
            [
                'campaign:campaigns:view',
                'campaign:campaigns:create',
                'campaign:campaigns:edit',
                'campaign:campaigns:delete',
                'campaign:campaigns:publish',

            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['campaign:campaigns:view']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $session->get('mautic.campaign.limit', $this->get('mautic.helper.core_parameters')->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.campaign.filter', ''));
        $session->set('mautic.campaign.filter', $search);

        $filter = ['string' => $search, 'force' => []];

        $currentFilters = $session->get('mautic.campaign.list_filters', []);
        $updatedFilters = $this->request->get('filters', false);

        $sourceLists = $model->getSourceLists();
        $listFilters = [
            'filters' => [
                'placeholder' => $this->get('translator')->trans('mautic.campaign.filter.placeholder'),
                'multiple'    => true,
                'groups'      => [
                    'mautic.campaign.leadsource.form' => [
                        'options' => $sourceLists['forms'],
                        'prefix'  => 'form',
                    ],
                    'mautic.campaign.leadsource.list' => [
                        'options' => $sourceLists['lists'],
                        'prefix'  => 'list',
                    ],
                ],
            ],
        ];

        if ($updatedFilters) {
            // Filters have been updated

            // Parse the selected values
            $newFilters     = [];
            $updatedFilters = json_decode($updatedFilters, true);

            if ($updatedFilters) {
                foreach ($updatedFilters as $updatedFilter) {
                    list($clmn, $fltr) = explode(':', $updatedFilter);

                    $newFilters[$clmn][] = $fltr;
                }

                $currentFilters = $newFilters;
            } else {
                $currentFilters = [];
            }
        }
        $session->set('mautic.campaign.list_filters', $currentFilters);

        $joinLists = $joinForms = false;
        if (!empty($currentFilters)) {
            $listIds = $catIds = [];
            foreach ($currentFilters as $type => $typeFilters) {
                $listFilters['filters'] ['groups']['mautic.campaign.leadsource.'.$type]['values'] = $typeFilters;

                foreach ($typeFilters as $fltr) {
                    if ($type == 'list') {
                        $listIds[] = (int) $fltr;
                    } else {
                        $formIds[] = (int) $fltr;
                    }
                }
            }

            if (!empty($listIds)) {
                $joinLists         = true;
                $filter['force'][] = ['column' => 'l.id', 'expr' => 'in', 'value' => $listIds];
            }

            if (!empty($formIds)) {
                $joinForms         = true;
                $filter['force'][] = ['column' => 'f.id', 'expr' => 'in', 'value' => $formIds];
            }
        }

        $orderBy    = $session->get('mautic.campaign.orderby', 'c.name');
        $orderByDir = $session->get('mautic.campaign.orderbydir', 'ASC');

        $campaigns = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
                'joinLists'  => $joinLists,
                'joinForms'  => $joinForms,
            ]
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
            $returnUrl = $this->generateUrl('mautic_campaign_index', ['page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_campaign_index',
                        'mauticContent' => 'campaign',
                    ],
                ]
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.campaign.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'items'       => $campaigns,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'tmpl'        => $tmpl,
                    'filters'     => $listFilters,
                ],
                'contentTemplate' => 'MauticCampaignBundle:Campaign:list.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign',
                    'route'         => $this->generateUrl('mautic_campaign_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * View a specific campaign.
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        $page = $this->get('session')->get('mautic.campaign.page', 1);

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model = $this->getModel('campaign');

        $security = $this->get('mautic.security');
        $entity   = $model->getEntity($objectId);

        $permissions = $security->isGranted(
            [
                'campaign:campaigns:view',
                'campaign:campaigns:create',
                'campaign:campaigns:edit',
                'campaign:campaigns:delete',
                'campaign:campaigns:publish',
            ],
            'RETURN_ARRAY'
        );

        if ($entity === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_campaign_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_campaign_index',
                        'mauticContent' => 'campaign',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.campaign.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$permissions['campaign:campaigns:view']) {
            return $this->accessDenied();
        }

        // Init the date range filter form
        $dateRangeValues = $this->request->get('daterange', []);
        $action          = $this->generateUrl('mautic_campaign_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $action]);

        /** @var LeadEventLogRepository $eventLogRepo */
        $eventLogRepo = $this->getDoctrine()->getManager()->getRepository('MauticCampaignBundle:LeadEventLog');
        $events       = $model->getEventRepository()->getCampaignEvents($entity->getId());
        $leadCount    = $model->getRepository()->getCampaignLeadCount($entity->getId());

        $campaignLogCounts = $eventLogRepo->getCampaignLogCounts($entity->getId(), true);

        $sortedEvents = [
            'decision'  => [],
            'action'    => [],
            'condition' => [],
        ];
        foreach ($events as $event) {
            $event['logCount']                   = (isset($campaignLogCounts[$event['id']])) ? (int) $campaignLogCounts[$event['id']] : 0;
            $event['percent']                    = ($leadCount) ? round($event['logCount'] / $leadCount * 100) : 0;
            $sortedEvents[$event['eventType']][] = $event;
        }

        $stats = $model->getCampaignMetricsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['campaign_id' => $objectId]
        );

        // Audit Log
        $logs = $this->getModel('core.auditLog')->getLogForObject('campaign', $objectId, $entity->getDateAdded());

        return $this->delegateView(
            [
                'viewParameters' => [
                    'campaign'      => $entity,
                    'permissions'   => $permissions,
                    'security'      => $security,
                    'logs'          => $logs,
                    'stats'         => $stats,
                    'events'        => $sortedEvents,
                    'sources'       => $model->getLeadSources($entity),
                    'dateRangeForm' => $dateRangeForm->createView(),
                    'campaignLeads' => $this->forward(
                        'MauticCampaignBundle:Campaign:contacts',
                        [
                            'objectId'   => $entity->getId(),
                            'page'       => $this->get('session')->get('mautic.campaign.contact.page', 1),
                            'ignoreAjax' => true,
                        ]
                    )->getContent(),
                ],
                'contentTemplate' => 'MauticCampaignBundle:Campaign:details.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign',
                    'route'         => $this->generateUrl(
                        'mautic_campaign_action',
                        [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @param     $objectId
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsAction($objectId, $page = 1)
    {
        return $this->generateContactsGrid(
            $objectId,
            $page,
            'campaign:campaigns:view',
            'campaign',
            'campaign_leads',
            null,
            'campaign_id',
            ['manually_removed' => 0]
        );
    }

    /**
     * Generates new form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model = $this->getModel('campaign');

        $entity  = $model->getEntity();
        $session = $this->get('session');

        if (!$this->get('mautic.security')->isGranted('campaign:campaigns:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->get('session')->get('mautic.campaign.page', 1);

        $sessionId = $this->request->request->get('campaign[sessionId]', 'mautic_'.sha1(uniqid(mt_rand(), true)), true);

        //set added/updated events
        list($modifiedEvents, $deletedEvents, $campaignEvents) = $this->getSessionEvents($sessionId);

        //set added/updated sources
        list($addedSources, $deletedSources, $currentSources) = $this->getSessionSources($sessionId);

        //setup the form
        $action = $this->generateUrl('mautic_campaign_action', ['objectAction' => 'new']);
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        //get event settings
        $eventSettings = $model->getEvents();

        $campaignSources = [];

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
                                $this->get('translator')->trans('mautic.campaign.form.events.notempty', [], 'validators')
                            )
                        );
                        $valid = false;
                    } elseif (empty($currentSources['lists']) && empty($currentSources['forms'])) {
                        //set the error
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.campaign.form.sources.notempty', [], 'validators')
                            )
                        );
                        $valid = false;
                    } else {
                        // Set lead sources
                        $model->setLeadSources($entity, $addedSources, $deletedSources);

                        $connections = $session->get('mautic.campaign.'.$sessionId.'.events.canvassettings');
                        // Build and set Event entities
                        $model->setEvents($entity, $campaignEvents, $connections, $deletedEvents);

                        // Persist to the database before building connection so that IDs are available
                        $model->saveEntity($entity);

                        // Update canvas settings with new event IDs then save
                        $model->setCanvasSettings($entity, $connections);

                        $this->addFlash(
                            'mautic.core.notice.created',
                            [
                                '%name%'      => $entity->getName(),
                                '%menu_link%' => 'mautic_campaign_index',
                                '%url%'       => $this->generateUrl(
                                    'mautic_campaign_action',
                                    [
                                        'objectAction' => 'edit',
                                        'objectId'     => $entity->getId(),
                                    ]
                                ),
                            ]
                        );

                        if ($form->get('buttons')->get('save')->isClicked()) {
                            $viewParameters = [
                                'objectAction' => 'view',
                                'objectId'     => $entity->getId(),
                            ];
                            $returnUrl = $this->generateUrl('mautic_campaign_action', $viewParameters);
                            $template  = 'MauticCampaignBundle:Campaign:view';
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
                            $campaignSources[$type] = [
                                'sourceType' => $type,
                                'campaignId' => $sessionId,
                                'names'      => implode(', ', array_intersect_key($sourceList, array_flip($sources))),
                            ];
                        }
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_campaign_index', $viewParameters);
                $template       = 'MauticCampaignBundle:Campaign:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                //clear temporary fields
                $this->clearSessionComponents($sessionId);

                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_campaign_index',
                            'mauticContent' => 'campaign',
                        ],
                    ]
                );
            }
        } else {
            //clear out existing fields in case the form was refreshed, browser closed, etc
            $this->clearSessionComponents($sessionId);
            $modifiedEvents = $deletedEvents = $campaignSources = [];

            $form->get('sessionId')->setData($sessionId);
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'eventSettings' => $eventSettings,

                    'campaignEvents'  => $modifiedEvents,
                    'campaignSources' => $campaignSources,
                    'deletedEvents'   => $deletedEvents,
                    'tmpl'            => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity'          => $entity,
                    'form'            => $form->createView(),
                ],
                'contentTemplate' => 'MauticCampaignBundle:Campaign:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign',
                    'route'         => $this->generateUrl(
                        'mautic_campaign_action',
                        [
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int|string $objectId
     * @param bool       $ignorePost
     * @param Campaign   $clonedEntity
     * @param array      $currentSources
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction($objectId, $ignorePost = false, Campaign $clonedEntity = null, array $currentSources = null)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model     = $this->getModel('campaign');
        $formData  = $this->request->request->get('campaign');
        $sessionId = isset($formData['sessionId']) ? $formData['sessionId'] : null;
        $session   = $this->get('session');
        $isClone   = false;
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
        $page = $this->get('session')->get('mautic.campaign.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_campaign_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign',
            ],
        ];
        //form not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.campaign.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->get('mautic.security')->isGranted('campaign:campaigns:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'campaign');
        }

        $action = $this->generateUrl('mautic_campaign_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
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
                                $this->get('translator')->trans('mautic.campaign.form.events.notempty', [], 'validators')
                            )
                        );
                        $valid = false;
                    } elseif (empty($currentSources['lists']) && empty($currentSources['forms'])) {
                        //set the error
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.campaign.form.sources.notempty', [], 'validators')
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
                            $model->setEvents($entity, $campaignEvents, $connections, $deletedEvents);

                            // Update canvas settings with new event IDs if applicable then save
                            $model->setCanvasSettings($entity, $connections);

                            if (!empty($deletedEvents)) {
                                $this->getModel('campaign.event')->deleteEvents($entity->getEvents()->toArray(), $deletedEvents);
                            }
                        }

                        $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                        if ($entity->getId() !== $objectId) {
                            // No longer a clone - this is important for Apply
                            $isClone  = false;
                            $objectId = $entity->getId();
                        }

                        $this->addFlash(
                            'mautic.core.notice.updated',
                            [
                                '%name%'      => $entity->getName(),
                                '%menu_link%' => 'mautic_campaign_index',
                                '%url%'       => $this->generateUrl(
                                    'mautic_campaign_action',
                                    [
                                        'objectAction' => 'edit',
                                        'objectId'     => $entity->getId(),
                                    ]
                                ),
                            ]
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

                $viewParameters = [
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                ];

                if (!$isClone) {
                    $postActionVars = array_merge(
                        $postActionVars,
                        [
                            'returnUrl'       => $this->generateUrl('mautic_campaign_action', $viewParameters),
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => 'MauticCampaignBundle:Campaign:view',
                        ]
                    );
                } // else redirect to index since there is no view page for the cancelled clone

                return $this->postActionRedirect($postActionVars);
            } else {
                //rebuild everything to include new ids if valid
                $cleanSlate = $valid;

                if ($valid) {
                    // Rebuild the form with new action so that apply doesn't keep creating a clone
                    $action = $this->generateUrl('mautic_campaign_action', ['objectAction' => 'edit', 'objectId' => $entity->getId()]);
                    $form   = $model->createForm($entity, $this->get('form.factory'), $action);
                }
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
            $campaignEvents = [];

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
            $deletedEvents = [];

            //load sources to session
            if (!$isClone || empty($currentSources)) {
                $currentSources = $model->getLeadSources($objectId);
            }

            $this->setSessionSources($objectId, $currentSources, $isClone);
        }

        $campaignSources = [];
        if (isset($currentSources) && is_array($currentSources)) {
            foreach ($currentSources as $type => $sources) {
                if (!empty($sources)) {
                    $sourceList             = $model->getSourceLists($type);
                    $campaignSources[$type] = [
                        'sourceType' => $type,
                        'campaignId' => $objectId,
                        'names'      => implode(', ', array_intersect_key($sourceList, $sources)),
                    ];
                }
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'eventSettings'   => $eventSettings,
                    'campaignEvents'  => $campaignEvents,
                    'campaignSources' => $campaignSources,
                    'deletedEvents'   => $deletedEvents,
                    'tmpl'            => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity'          => $entity,
                    'form'            => $form->createView(),
                ],
                'contentTemplate' => 'MauticCampaignBundle:Campaign:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_campaign_index',
                    'mauticContent' => 'campaign',
                    'route'         => $this->generateUrl(
                        'mautic_campaign_action',
                        [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Clone an entity.
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        $model    = $this->getModel('campaign');
        $campaign = $model->getEntity($objectId);

        // Generate temporary ID
        $tempId         = 'mautic_'.sha1(uniqid(mt_rand(), true));
        $currentSources = [];

        if ($campaign != null) {
            if (!$this->get('mautic.security')->isGranted('campaign:campaigns:create')) {
                return $this->accessDenied();
            }

            // load sources to session
            $currentSources = $model->getLeadSources($objectId);

            // Get the events that need to be duplicated as well
            $events = $campaign->getEvents()->toArray();

            // Clone the campaign
            /** @var \Mautic\CampaignBundle\Entity\Campaign $campaign */
            $campaign = clone $campaign;
            $campaign->setIsPublished(false);

            // Clone the campaign's events
            foreach ($events as $event) {
                $tempEventId = 'new'.$event->getId();

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
     * Deletes the entity.
     *
     * @param   $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.campaign.page', 1);
        $returnUrl = $this->generateUrl('mautic_campaign_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->getModel('campaign');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.campaign.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->isGranted('campaign:campaigns:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'campaign');
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
                    '%id%'   => $objectId,
                ],
            ];
        } //else don't do anything

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
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.campaign.page', 1);
        $returnUrl = $this->generateUrl('mautic_campaign_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_campaign_index',
                'mauticContent' => 'campaign',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->getModel('campaign');
            $ids       = json_decode($this->request->query->get('ids', ''));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.campaign.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->isGranted('campaign:campaigns:delete')) {
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

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.campaign.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } //else don't do anything

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
     * Clear field and events from the session.
     *
     * @param $id
     */
    private function clearSessionComponents($id)
    {
        $session = $this->get('session');
        $session->remove('mautic.campaign.'.$id.'.events.modified');
        $session->remove('mautic.campaign.'.$id.'.events.deleted');
        $session->remove('mautic.campaign.'.$id.'.events.canvassettings');
        $session->remove('mautic.campaign.'.$id.'.leadsources.current');
        $session->remove('mautic.campaign.'.$id.'.leadsources.modified');
        $session->remove('mautic.campaign.'.$id.'.leadsources.deleted');
    }

    /**
     * Get events from session.
     *
     * @param $id
     *
     * @return array
     */
    private function getSessionEvents($id)
    {
        $session = $this->get('session');

        $modifiedEvents = $session->get('mautic.campaign.'.$id.'.events.modified', []);
        $deletedEvents  = $session->get('mautic.campaign.'.$id.'.events.deleted', []);

        $events = array_diff_key($modifiedEvents, array_flip($deletedEvents));

        return [$modifiedEvents, $deletedEvents, $events];
    }

    /**
     * Set events to session.
     *
     * @param $id
     * @param $events
     */
    private function setSessionEvents($id, $events)
    {
        $session = $this->get('session');

        $session->set('mautic.campaign.'.$id.'.events.modified', $events);
    }

    /**
     * Get events from session.
     *
     * @param $id
     * @param $isClone
     *
     * @return array
     */
    private function getSessionSources($id, $isClone = false)
    {
        $session = $this->get('session');

        $currentSources  = $session->get('mautic.campaign.'.$id.'.leadsources.current', []);
        $modifiedSources = $session->get('mautic.campaign.'.$id.'.leadsources.modified', []);

        if ($currentSources === $modifiedSources) {
            if ($isClone) {
                // Clone hasn't saved the sources yet so return the current list as added
                return [$currentSources, [], $currentSources];
            } else {
                return [[], [], $currentSources];
            }
        }

        // Deleted sources
        $deletedSources = [];
        foreach ($currentSources as $type => $sources) {
            if (isset($modifiedSources[$type])) {
                $deletedSources[$type] = array_diff($sources, $modifiedSources[$type]);
            } else {
                $deletedSources[$type] = $sources;
            }
        }

        // Added sources
        $addedSources = [];
        foreach ($modifiedSources as $type => $sources) {
            if (isset($currentSources[$type])) {
                $addedSources[$type] = array_diff($sources, $currentSources[$type]);
            } else {
                $addedSources[$type] = $sources;
            }
        }

        return [$addedSources, $deletedSources, $modifiedSources];
    }

    /**
     * Set sources to session.
     *
     * @param $id
     * @param $sources
     */
    private function setSessionSources($id, $sources, $isClone = false)
    {
        $session = $this->get('session');
        foreach ($sources as $type => $typeSources) {
            if (!empty($typeSources)) {
                $typeSources    = array_keys($typeSources);
                $sources[$type] = array_combine($typeSources, $typeSources);
            }
        }
        $session->set('mautic.campaign.'.$id.'.leadsources.current', ($isClone) ? [] : $sources);
        $session->set('mautic.campaign.'.$id.'.leadsources.modified', $sources);
    }
}
