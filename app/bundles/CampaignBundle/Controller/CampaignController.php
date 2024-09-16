<?php

namespace Mautic\CampaignBundle\Controller;

use Doctrine\DBAL\Cache\CacheException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CampaignBundle\Entity\SummaryRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class CampaignController extends AbstractStandardFormController
{
    use EntityContactsTrait;

    /**
     * @var array
     */
    protected $addedSources = [];

    /**
     * @var array
     */
    protected $campaignEvents = [];

    /**
     * @var array
     */
    protected $campaignSources = [];

    /**
     * @var array
     */
    protected $connections = [];

    /**
     * @var array
     */
    protected $deletedEvents = [];

    /**
     * @var array
     */
    protected $deletedSources = [];

    /**
     * @var array
     */
    protected $listFilters = [];

    /**
     * @var array
     */
    protected $modifiedEvents = [];

    protected $sessionId;

    /**
     * @return array
     */
    protected function getPermissions()
    {
        //set some permissions
        return (array) $this->get('mautic.security')->isGranted(
            [
                'campaign:campaigns:viewown',
                'campaign:campaigns:viewother',
                'campaign:campaigns:create',
                'campaign:campaigns:editown',
                'campaign:campaigns:editother',
                'campaign:campaigns:cloneown',
                'campaign:campaigns:cloneother',
                'campaign:campaigns:deleteown',
                'campaign:campaigns:deleteother',
                'campaign:campaigns:publishown',
                'campaign:campaigns:publishother',
            ],
            'RETURN_ARRAY'
        );
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|RedirectResponse
     */
    public function batchDeleteAction()
    {
        return $this->batchDeleteStandard();
    }

    /**
     * Clone an entity.
     *
     * @param $objectId
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        return $this->cloneStandard($objectId);
    }

    /**
     * @param string|int $objectId
     * @param int        $page
     * @param int|null   $count
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function contactsAction($objectId, $page = 1, $count = null, \DateTimeInterface $dateFrom = null, \DateTimeInterface $dateTo = null)
    {
        $session = $this->get('session');
        $session->set('mautic.campaign.contact.page', $page);

        return $this->generateContactsGrid(
            $objectId,
            $page,
            'campaign:campaigns:view',
            'campaign',
            'campaign_leads',
            null,
            'campaign_id',
            ['manually_removed' => 0],
            null,
            null,
            [],
            null,
            'entity.lead_id',
            'DESC',
            $count,
            $dateFrom,
            $dateTo
        );
    }

    /**
     * Deletes the entity.
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|RedirectResponse
     */
    public function deleteAction($objectId)
    {
        return $this->deleteStandard($objectId);
    }

    /**
     * @param      $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        return $this->editStandard($objectId, $ignorePost);
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = null)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
            [
                'campaign:campaigns:view',
                'campaign:campaigns:viewown',
                'campaign:campaigns:viewother',
                'campaign:campaigns:create',
                'campaign:campaigns:edit',
                'campaign:campaigns:editown',
                'campaign:campaigns:editother',
                'campaign:campaigns:delete',
                'campaign:campaigns:deleteown',
                'campaign:campaigns:deleteother',
                'campaign:campaigns:publish',
                'campaign:campaigns:publishown',
                'campaign:campaigns:publishother',
            ],
            'RETURN_ARRAY',
            null,
            true
        );

        if (!$permissions['campaign:campaigns:view']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $session = $this->get('session');
        if (empty($page)) {
            $page = $session->get('mautic.campaign.page', 1);
        }

        //set limits
        $limit = $session->get('mautic.campaign.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.campaign.filter', ''));
        $session->set('mautic.campaign.filter', $search);

        $filter = ['string' => $search, 'force' => []];

        $model = $this->getModel('campaign');

        if (!$permissions[$this->getPermissionBase().':viewother']) {
            $filter['force'][] = ['column' => 'c.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        $orderBy    = $session->get('mautic.campaign.orderby', 'c.dateModified');
        $orderByDir = $session->get('mautic.campaign.orderbydir', $this->getDefaultOrderDirection());

        [$count, $items] = $this->getIndexItems($start, $limit, $filter, $orderBy, $orderByDir);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = (1 === $count) ? 1 : (((ceil($count / $limit)) ?: 1) ?: 1);

            $session->set('mautic.campaign.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_campaign_index', ['page' => $lastPage]);

            return $this->postActionRedirect(
                $this->getPostActionRedirectArguments(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => ['page' => $lastPage],
                        'contentTemplate' => 'MauticCampaignBundle:Campaign:index',
                        'passthroughVars' => [
                            'mauticContent' => 'campaign',
                        ],
                    ],
                    'index'
                )
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.campaign.page', $page);

        $viewParameters = [
            'permissionBase'  => $this->getPermissionBase(),
            'mauticContent'   => $this->getJsLoadMethodPrefix(),
            'sessionVar'      => $this->getSessionBase(),
            'actionRoute'     => $this->getActionRoute(),
            'indexRoute'      => $this->getIndexRoute(),
            'tablePrefix'     => $model->getRepository()->getTableAlias(),
            'modelName'       => $this->getModelName(),
            'translationBase' => $this->getTranslationBase(),
            'searchValue'     => $search,
            'items'           => $items,
            'totalItems'      => $count,
            'page'            => $page,
            'limit'           => $limit,
            'permissions'     => $permissions,
            'tmpl'            => $this->request->get('tmpl', 'index'),
        ];

        return $this->delegateView(
            $this->getViewArguments(
                [
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $this->getTemplateName('list.html.php'),
                    'passthroughVars' => [
                        'mauticContent' => $this->getJsLoadMethodPrefix(),
                        'route'         => $this->generateUrl($this->getIndexRoute(), ['page' => $page]),
                    ],
                ],
                'index'
            )
        );
    }

    /**
     * Generates new form and processes post data.
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        /** @var CampaignModel $model */
        $model    = $this->getModel('campaign');
        $campaign = $model->getEntity();

        if (!$this->get('mautic.security')->isGranted('campaign:campaigns:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->get('session')->get('mautic.campaign.page', 1);

        $options = $this->getEntityFormOptions();
        $action  = $this->generateUrl('mautic_campaign_action', ['objectAction' => 'new']);
        $form    = $model->createForm($campaign, $this->get('form.factory'), $action, $options);

        ///Check for a submitted form and process it
        $isPost = 'POST' === $this->request->getMethod();
        $this->beforeFormProcessed($campaign, $form, 'new', $isPost);

        if ($isPost) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    if ($valid = $this->beforeEntitySave($campaign, $form, 'new')) {
                        $campaign->setDateModified(new \DateTime());
                        $model->saveEntity($campaign);
                        $this->afterEntitySave($campaign, $form, 'new', $valid);

                        if (method_exists($this, 'viewAction')) {
                            $viewParameters = ['objectId' => $campaign->getId(), 'objectAction' => 'view'];
                            $returnUrl      = $this->generateUrl('mautic_campaign_action', $viewParameters);
                            $template       = 'MauticCampaignBundle:Campaign:view';
                        } else {
                            $viewParameters = ['page' => $page];
                            $returnUrl      = $this->generateUrl('mautic_campaign_index', $viewParameters);
                            $template       = 'MauticCampaignBundle:Campaign:index';
                        }
                    }
                }

                $this->afterFormProcessed($valid, $campaign, $form, 'new');
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl($this->getIndexRoute(), $viewParameters);
                $template       = 'MauticCampaignBundle:Campaign:index';
            }

            $passthrough = [
                'mauticContent' => 'cammpaign',
            ];

            if ($isInPopup = isset($form['updateSelect'])) {
                $template    = false;
                $passthrough = array_merge(
                    $passthrough,
                    $this->getUpdateSelectParams($form['updateSelect']->getData(), $campaign)
                );
            }

            if ($cancelled || ($valid && !$this->isFormApplied($form))) {
                if ($isInPopup) {
                    $passthrough['closeModal'] = true;
                }

                return $this->postActionRedirect(
                    $this->getPostActionRedirectArguments(
                        [
                            'returnUrl'       => $returnUrl,
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template,
                            'passthroughVars' => $passthrough,
                            'entity'          => $campaign,
                        ],
                        'new'
                    )
                );
            } elseif ($valid && $this->isFormApplied($form)) {
                return $this->editAction($campaign->getId(), true);
            }
        }

        $delegateArgs = [
            'viewParameters' => [
                'permissionBase'  => $model->getPermissionBase(),
                'mauticContent'   => 'campaign',
                'actionRoute'     => 'mautic_campaign_action',
                'indexRoute'      => 'mautic_campaign_index',
                'tablePrefix'     => 'c',
                'modelName'       => 'campaign',
                'translationBase' => $this->getTranslationBase(),
                'tmpl'            => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'          => $campaign,
                'form'            => $this->getFormView($form, 'new'),
            ],
            'contentTemplate' => 'MauticCampaignBundle:Campaign:form.html.php',
            'passthroughVars' => [
                'mauticContent' => 'campaign',
                'route'         => $this->generateUrl(
                    'mautic_campaign_action',
                    [
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                        'objectId'     => ($campaign) ? $campaign->getId() : 0,
                    ]
                ),
                'validationError' => $this->getFormErrorForBuilder($form),
            ],
            'entity' => $campaign,
            'form'   => $form,
        ];

        return $this->delegateView(
            $this->getViewArguments($delegateArgs, 'new')
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
        return $this->viewStandard($objectId, $this->getModelName(), null, null, 'campaign');
    }

    /**
     * @param Campaign $campaign
     * @param Campaign $oldCampaign
     */
    protected function afterEntityClone($campaign, $oldCampaign)
    {
        $tempId   = 'mautic_'.sha1(uniqid(mt_rand(), true));
        $objectId = $oldCampaign->getId();

        // Get the events that need to be duplicated as well
        $events = $oldCampaign->getEvents()->toArray();

        $campaign->setIsPublished(false);

        // Clone the campaign's events
        /** @var Event $event */
        foreach ($events as $event) {
            $tempEventId = 'new'.$event->getId();

            $clone = clone $event;
            $clone->nullId();
            $clone->setCampaign($campaign);
            $clone->setTempId($tempEventId);

            // Just wipe out the parent as it'll be generated when the cloned entity is saved
            $clone->setParent(null);

            if (CampaignActionJumpToEventSubscriber::EVENT_NAME === $clone->getType()) {
                // Update properties to point to the new temp ID
                $properties                = $clone->getProperties();
                $properties['jumpToEvent'] = 'new'.$properties['jumpToEvent'];

                $clone->setProperties($properties);
            }

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

        // Simulate edit
        $campaign->setCanvasSettings($canvasSettings);
        $this->setSessionCanvasSettings($tempId, $canvasSettings);
        $tempId = $this->getCampaignSessionId($campaign, 'clone', $tempId);

        $campaignSources = $this->getCampaignModel()->getLeadSources($objectId);
        $this->prepareCampaignSourcesForEdit($tempId, $campaignSources);
    }

    /**
     * @param object    $entity
     * @param string    $action
     * @param bool|null $persistConnections
     */
    protected function afterEntitySave($entity, Form $form, $action, $persistConnections = null)
    {
        if ($persistConnections) {
            // Update canvas settings with new event IDs then save
            $this->connections = $this->getCampaignModel()->setCanvasSettings($entity, $this->connections);
        } else {
            // Just update and add to entity
            $this->connections = $this->getCampaignModel()->setCanvasSettings($entity, $this->connections, false, $this->modifiedEvents);
        }
    }

    /**
     * @param      $isValid
     * @param      $entity
     * @param      $action
     * @param bool $isClone
     */
    protected function afterFormProcessed($isValid, $entity, Form $form, $action, $isClone = false)
    {
        if (!$isValid) {
            // Add the canvas settings to the entity to be able to rebuild it
            $this->afterEntitySave($entity, $form, $action, false);
        } else {
            $this->clearSessionComponents($this->sessionId);
            $this->sessionId = $entity->getId();
        }
    }

    /**
     * @param      $entity
     * @param      $action
     * @param      $isPost
     * @param null $objectId
     * @param bool $isClone
     */
    protected function beforeFormProcessed($entity, Form $form, $action, $isPost, $objectId = null, $isClone = false)
    {
        $sessionId = $this->getCampaignSessionId($entity, $action, $objectId);
        //set added/updated events
        [$this->modifiedEvents, $this->deletedEvents, $this->campaignEvents] = $this->getSessionEvents($sessionId);

        //set added/updated sources
        [$this->addedSources, $this->deletedSources, $campaignSources]     = $this->getSessionSources($sessionId, $isClone);
        $this->connections                                                 = $this->getSessionCanvasSettings($sessionId);

        if ($isPost) {
            $this->getCampaignModel()->setCanvasSettings($entity, $this->connections, false, $this->modifiedEvents);
            $this->prepareCampaignSourcesForEdit($sessionId, $campaignSources, true);
        } else {
            if (!$isClone) {
                //clear out existing fields in case the form was refreshed, browser closed, etc
                $this->clearSessionComponents($sessionId);
                $this->modifiedEvents = $this->campaignSources = [];

                if ($entity->getId()) {
                    $campaignSources = $this->getCampaignModel()->getLeadSources($entity->getId());
                    $this->prepareCampaignSourcesForEdit($sessionId, $campaignSources);

                    $this->setSessionCanvasSettings($sessionId, $entity->getCanvasSettings());
                }
            }

            $this->deletedEvents = [];

            $form->get('sessionId')->setData($sessionId);

            $this->prepareCampaignEventsForEdit($entity, $sessionId, $isClone);
        }
    }

    /**
     * @param Campaign $entity
     * @param          $action
     * @param null     $objectId
     * @param bool     $isClone
     *
     * @return bool
     */
    protected function beforeEntitySave($entity, Form $form, $action, $objectId = null, $isClone = false)
    {
        if (empty($this->campaignEvents)) {
            //set the error
            $form->addError(
                new FormError(
                    $this->get('translator')->trans('mautic.campaign.form.events.notempty', [], 'validators')
                )
            );

            return false;
        }

        if (empty($this->campaignSources['lists']) && empty($this->campaignSources['forms'])) {
            //set the error
            $form->addError(
                new FormError(
                    $this->get('translator')->trans('mautic.campaign.form.sources.notempty', [], 'validators')
                )
            );

            return false;
        }

        if ($isClone) {
            [$this->addedSources, $this->deletedSources, $campaignSources] = $this->getSessionSources($objectId, $isClone);
            $this->getCampaignModel()->setLeadSources($entity, $campaignSources, []);
            // If this is a clone, we need to save the entity first to properly build the events, sources and canvas settings
            $this->getCampaignModel()->getRepository()->saveEntity($entity);
            // Set as new so that timestamps are still hydrated
            $entity->setNew();
            $this->sessionId = $entity->getId();
        }

        // Set lead sources
        $this->getCampaignModel()->setLeadSources($entity, $this->addedSources, $this->deletedSources);

        // Build and set Event entities
        $this->getCampaignModel()->setEvents($entity, $this->campaignEvents, $this->connections, $this->deletedEvents);

        if ('edit' === $action && null !== $this->connections) {
            if (!empty($this->deletedEvents)) {
                /** @var EventModel $eventModel */
                $eventModel = $this->getModel('campaign.event');
                $eventModel->deleteEvents($entity->getEvents()->toArray(), $this->deletedEvents);
            }
        }

        return true;
    }

    /**
     * Clear field and events from the session.
     *
     * @param $id
     */
    protected function clearSessionComponents($id)
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
     * @return CampaignModel
     */
    protected function getCampaignModel()
    {
        /** @var CampaignModel $model */
        $model = $this->getModel($this->getModelName());

        return $model;
    }

    /**
     * @param      $action
     * @param null $objectId
     *
     * @return int|string|null
     */
    protected function getCampaignSessionId(Campaign $campaign, $action, $objectId = null)
    {
        if (isset($this->sessionId)) {
            return $this->sessionId;
        }

        if ($objectId) {
            $sessionId = $objectId;
        } elseif ('new' === $action && empty($sessionId)) {
            $sessionId = 'mautic_'.sha1(uniqid(mt_rand(), true));
            if ($this->request->request->has('campaign')) {
                $campaign  = $this->request->request->get('campaign', []);
                $sessionId = $campaign['sessionId'] ?? $sessionId;
            }
        } elseif ('edit' === $action) {
            $sessionId = $campaign->getId();
        }

        $this->sessionId = $sessionId;

        return $sessionId;
    }

    /**
     * @return string
     */
    protected function getControllerBase()
    {
        return 'MauticCampaignBundle:Campaign';
    }

    /**
     * @param $start
     * @param $limit
     * @param $filter
     * @param $orderBy
     * @param $orderByDir
     */
    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = [])
    {
        $session        = $this->get('session');
        $currentFilters = $session->get('mautic.campaign.list_filters', []);
        $updatedFilters = $this->request->get('filters', false);

        $sourceLists = $this->getCampaignModel()->getSourceLists();
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
                    [$clmn, $fltr] = explode(':', $updatedFilter);

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
                $listFilters['filters']['groups']['mautic.campaign.leadsource.'.$type]['values'] = $typeFilters;

                foreach ($typeFilters as $fltr) {
                    if ('list' == $type) {
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

        // Store for customizeViewArguments
        $this->listFilters = $listFilters;

        return parent::getIndexItems(
            $start,
            $limit,
            $filter,
            $orderBy,
            $orderByDir,
            [
                'joinLists' => $joinLists,
                'joinForms' => $joinForms,
            ]
        );
    }

    /**
     * @return string
     */
    protected function getModelName()
    {
        return 'campaign';
    }

    /**
     * @param $action
     *
     * @return array
     */
    protected function getPostActionRedirectArguments(array $args, $action)
    {
        switch ($action) {
            case 'new':
            case 'edit':
                if (!empty($args['entity'])) {
                    $sessionId = $this->getCampaignSessionId($args['entity'], $action);
                    $this->clearSessionComponents($sessionId);
                }
                break;
        }

        return $args;
    }

    /**
     * Get events from session.
     *
     * @param $id
     *
     * @return array
     */
    protected function getSessionEvents($id)
    {
        $session = $this->get('session');

        $modifiedEvents = $session->get('mautic.campaign.'.$id.'.events.modified', []);
        $deletedEvents  = $session->get('mautic.campaign.'.$id.'.events.deleted', []);

        $events = array_diff_key($modifiedEvents, array_flip($deletedEvents));

        return [$modifiedEvents, $deletedEvents, $events];
    }

    /**
     * Get events from session.
     *
     * @param $id
     * @param $isClone
     *
     * @return array
     */
    protected function getSessionSources($id, $isClone = false)
    {
        $session = $this->get('session');

        $campaignSources = $session->get('mautic.campaign.'.$id.'.leadsources.current', []);
        $modifiedSources = $session->get('mautic.campaign.'.$id.'.leadsources.modified', []);

        if ($campaignSources === $modifiedSources) {
            if ($isClone) {
                // Clone hasn't saved the sources yet so return the current list as added
                return [$campaignSources, [], $campaignSources];
            } else {
                return [[], [], $campaignSources];
            }
        }

        // Deleted sources
        $deletedSources = [];
        foreach ($campaignSources as $type => $sources) {
            if (isset($modifiedSources[$type])) {
                $deletedSources[$type] = array_diff_key($sources, $modifiedSources[$type]);
            } else {
                $deletedSources[$type] = $sources;
            }
        }

        // Added sources
        $addedSources = [];
        foreach ($modifiedSources as $type => $sources) {
            if (isset($campaignSources[$type])) {
                $addedSources[$type] = array_diff_key($sources, $campaignSources[$type]);
            } else {
                $addedSources[$type] = $sources;
            }
        }

        return [$addedSources, $deletedSources, $modifiedSources];
    }

    /**
     * @param string $action
     *
     * @throws CacheException
     */
    protected function getViewArguments(array $args, $action): array
    {
        /** @var EventCollector $eventCollector */
        $eventCollector = $this->get('mautic.campaign.event_collector');

        switch ($action) {
            case 'index':
                $args['viewParameters']['filters'] = $this->listFilters;
                break;
            case 'view':
                /** @var Campaign $entity */
                $entity   = $args['entity'];
                $objectId = $args['objectId'];
                // Init the date range filter form
                $dateRangeValues = $this->request->get('daterange', []);
                $action          = $this->generateUrl('mautic_campaign_action', ['objectAction' => 'view', 'objectId' => $objectId]);
                $dateRangeForm   = $this->get('form.factory')->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
                $events          = $this->getCampaignModel()->getEventRepository()->getCampaignEvents($entity->getId());
                $dateFrom        = null;
                $dateTo          = null;
                $dateToPlusOne   = null;
                $this->setCoreParametersHelper($this->get('mautic.config'));

                if ($this->coreParametersHelper->get('campaign_by_range')) {
                    $dateFrom      = new \DateTimeImmutable($dateRangeForm->get('date_from')->getData());
                    $dateTo        = new \DateTimeImmutable($dateRangeForm->get('date_to')->getData());
                    $dateToPlusOne = $dateTo->modify('+1 day');
                }

                $leadCount = $this->getCampaignModel()->getRepository()->getCampaignLeadCount($entity->getId());
                $logCounts = $this->processCampaignLogCounts($entity->getId(), $dateFrom, $dateToPlusOne);

                $campaignLogCounts          = $logCounts['campaignLogCounts'] ?? [];
                $campaignLogCountsProcessed = $logCounts['campaignLogCountsProcessed'] ?? [];

                $this->processCampaignEvents($events, $leadCount, $campaignLogCounts, $campaignLogCountsProcessed);
                $sortedEvents = $this->processCampaignEventsFromParentCondition($events);

                $stats = $this->getCampaignModel()->getCampaignMetricsLineChartData(
                    null,
                    new \DateTime($dateRangeForm->get('date_from')->getData()),
                    new \DateTime($dateRangeForm->get('date_to')->getData()),
                    null,
                    ['campaign_id' => $objectId]
                );

                $sourcesList = $this->getCampaignModel()->getSourceLists();

                $this->prepareCampaignSourcesForEdit($objectId, $sourcesList, true);
                $this->prepareCampaignEventsForEdit($entity, $objectId, true);

                $args['viewParameters'] = array_merge(
                    $args['viewParameters'],
                    [
                        'campaign'        => $entity,
                        'stats'           => $stats,
                        'events'          => $sortedEvents,
                        'eventSettings'   => $eventCollector->getEventsArray(),
                        'sources'         => $this->getCampaignModel()->getLeadSources($entity),
                        'dateRangeForm'   => $dateRangeForm->createView(),
                        'campaignSources' => $this->campaignSources,
                        'campaignEvents'  => $events,
                    ]
                );
                break;
            case 'new':
            case 'edit':
                $args['viewParameters'] = array_merge(
                    $args['viewParameters'],
                    [
                        'eventSettings'   => $eventCollector->getEventsArray(),
                        'campaignEvents'  => $this->campaignEvents,
                        'campaignSources' => $this->campaignSources,
                        'deletedEvents'   => $this->deletedEvents,
                    ]
                );
                break;
        }

        return $args;
    }

    /**
     * @param      $entity
     * @param      $objectId
     * @param bool $isClone
     *
     * @return array
     */
    protected function prepareCampaignEventsForEdit($entity, $objectId, $isClone = false)
    {
        //load existing events into session
        $campaignEvents = [];

        $existingEvents = $entity->getEvents()->toArray();
        $translator     = $this->get('translator');
        $dateHelper     = $this->get('mautic.helper.template.date');
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

            $label = false;
            switch ($event['triggerMode']) {
                case 'interval':
                    $label = $translator->trans(
                        'mautic.campaign.connection.trigger.interval.label'.('no' == $event['decisionPath'] ? '_inaction' : ''),
                        [
                            '%number%' => $event['triggerInterval'],
                            '%unit%'   => $translator->trans(
                                'mautic.campaign.event.intervalunit.'.$event['triggerIntervalUnit'],
                                ['%count%' => $event['triggerInterval']]
                            ),
                        ]
                    );
                    break;
                case 'date':
                    $label = $translator->trans(
                        'mautic.campaign.connection.trigger.date.label'.('no' == $event['decisionPath'] ? '_inaction' : ''),
                        [
                            '%full%' => $dateHelper->toFull($event['triggerDate']),
                            '%time%' => $dateHelper->toTime($event['triggerDate']),
                            '%date%' => $dateHelper->toShort($event['triggerDate']),
                        ]
                    );
                    break;
            }
            if ($label) {
                $event['label'] = $label;
            }

            $campaignEvents[$id] = $event;
        }

        $this->modifiedEvents = $this->campaignEvents = $campaignEvents;
        $this->get('session')->set('mautic.campaign.'.$objectId.'.events.modified', $campaignEvents);
    }

    /**
     * @param $objectId
     * @param $campaignSources
     */
    protected function prepareCampaignSourcesForEdit($objectId, $campaignSources, $isPost = false)
    {
        $this->campaignSources = [];
        if (is_array($campaignSources)) {
            foreach ($campaignSources as $type => $sources) {
                if (!empty($sources)) {
                    $sourceList                   = $this->getModel('campaign')->getSourceLists($type);
                    $this->campaignSources[$type] = [
                        'sourceType' => $type,
                        'campaignId' => $objectId,
                        'names'      => implode(', ', array_intersect_key($sourceList, $sources)),
                    ];
                }
            }
        }

        if (!$isPost) {
            $session = $this->get('session');
            $session->set('mautic.campaign.'.$objectId.'.leadsources.current', $campaignSources);
            $session->set('mautic.campaign.'.$objectId.'.leadsources.modified', $campaignSources);
        }
    }

    /**
     * @param $sessionId
     * @param $canvasSettings
     */
    protected function setSessionCanvasSettings($sessionId, $canvasSettings)
    {
        $this->get('session')->set('mautic.campaign.'.$sessionId.'.events.canvassettings', $canvasSettings);
    }

    /**
     * @param $sessionId
     *
     * @return mixed
     */
    protected function getSessionCanvasSettings($sessionId)
    {
        return $this->get('session')->get('mautic.campaign.'.$sessionId.'.events.canvassettings');
    }

    /**
     * @return array<string, array<int|string, array<int|string, int|string>>>
     *
     * @throws CacheException
     */
    private function processCampaignLogCounts(int $id, ?\DateTimeImmutable $dateFrom, ?\DateTimeImmutable $dateToPlusOne): array
    {
        if ($this->coreParametersHelper->get('campaign_use_summary')) {
            /** @var SummaryRepository $summaryRepo */
            $summaryRepo                = $this->getDoctrine()->getManager()->getRepository(Summary::class);
            $campaignLogCounts          = $summaryRepo->getCampaignLogCounts($id, $dateFrom, $dateToPlusOne);
            $campaignLogCountsProcessed = $this->getCampaignLogCountsProcessed($campaignLogCounts);
        } else {
            /** @var LeadEventLogRepository $eventLogRepo */
            $eventLogRepo               = $this->getDoctrine()->getManager()->getRepository(LeadEventLog::class);
            $campaignLogCounts          = $eventLogRepo->getCampaignLogCounts($id, false, false, true, $dateFrom, $dateToPlusOne);
            $campaignLogCountsProcessed = $eventLogRepo->getCampaignLogCounts($id, false, false, false, $dateFrom, $dateToPlusOne);
        }

        return [
            'campaignLogCounts'          => $campaignLogCounts,
            'campaignLogCountsProcessed' => $campaignLogCountsProcessed,
        ];
    }

    /**
     * @param array<int, array<int|string, int|string>>        $events
     * @param array<int|string, array<int|string, int|string>> $campaignLogCounts
     * @param array<int|string, array<int|string, int|string>> $campaignLogCountsProcessed
     */
    private function processCampaignEvents(
        array &$events,
        int $leadCount,
        array $campaignLogCounts,
        array $campaignLogCountsProcessed
    ): void {
        foreach ($events as &$event) {
            $event['logCountForPending'] =
            $event['logCountProcessed']  =
            $event['percent']            =
            $event['yesPercent']         =
            $event['noPercent']          = 0;

            if (isset($campaignLogCounts[$event['id']])) {
                $loggedCount                 = array_sum($campaignLogCounts[$event['id']]);
                $logCountsProcessed          = isset($campaignLogCountsProcessed[$event['id']]) ? array_sum($campaignLogCountsProcessed[$event['id']]) : 0;
                $pending                     = $loggedCount - $logCountsProcessed;
                $event['logCountForPending'] = $pending;
                $event['logCountProcessed']  = $logCountsProcessed;
                [$totalNo, $totalYes]        = $campaignLogCounts[$event['id']];
                $total                       = $totalYes + $totalNo;

                if ($leadCount) {
                    $event['percent']    = min(100, max(0, round(($loggedCount / $total) * 100, 1)));
                    $event['yesPercent'] = min(100, max(0, round(($totalYes / $total) * 100, 1)));
                    $event['noPercent']  = min(100, max(0, round(($totalNo / $total) * 100, 1)));
                }
            }
        }
    }

    /**
     * @param array<int, array<int|string, int|string>> $events
     *
     * @return array<string, array<int, array<int|string, int|string>>>
     */
    private function processCampaignEventsFromParentCondition(array &$events): array
    {
        $sortedEvents = [
            'decision'  => [],
            'action'    => [],
            'condition' => [],
        ];

        // rewrite stats data from parent condition if exist
        foreach ($events as &$event) {
            if (!empty($event['decisionPath']) &&
                !empty($event['parent_id']) &&
                isset($events[$event['parent_id']]) &&
                'condition' !== $event['eventType']) {
                $parentEvent                 = $events[$event['parent_id']];
                $event['percent']            = $parentEvent['percent'];
                $event['yesPercent']         = $parentEvent['yesPercent'];
                $event['noPercent']          = $parentEvent['noPercent'];
                if ('yes' === $event['decisionPath']) {
                    $event['noPercent'] = 0;
                } else {
                    $event['yesPercent'] = 0;
                }
            }
            $sortedEvents[$event['eventType']][] = $event;
        }

        return $sortedEvents;
    }

    /**
     * @param array<int, array<int, string>> $campaignLogCounts
     *
     * @return array<int, array<int, string>>
     */
    private function getCampaignLogCountsProcessed(array &$campaignLogCounts): array
    {
        $campaignLogCountsProcessed = [];

        foreach ($campaignLogCounts as $eventId => $campaignLogCount) {
            $campaignLogCountsProcessed[$eventId][] = $campaignLogCount[2];
            unset($campaignLogCounts[$eventId][2]);
        }

        return $campaignLogCountsProcessed;
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }
}
