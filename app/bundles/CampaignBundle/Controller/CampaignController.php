<?php

namespace Mautic\CampaignBundle\Controller;

use Doctrine\DBAL\Cache\CacheException;
use Doctrine\Persistence\ManagerRegistry;
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
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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

    public function __construct(
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        private EventCollector $eventCollector,
        private DateHelper $dateHelper,
        ManagerRegistry $managerRegistry,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security,
    ) {
        parent::__construct($formFactory, $fieldHelper, $managerRegistry, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    protected function getPermissions(): array
    {
        // set some permissions
        return (array) $this->security->isGranted(
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
     * @return JsonResponse|RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        return $this->batchDeleteStandard($request);
    }

    /**
     * Clone an entity.
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function cloneAction(Request $request, $objectId)
    {
        return $this->cloneStandard($request, $objectId);
    }

    /**
     * @param string|int $objectId
     * @param int        $page
     * @param int|null   $count
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function contactsAction(
        Request $request,
        PageHelperFactoryInterface $pageHelperFactory,
        $objectId,
        $page = 1,
        $count = null,
        \DateTimeInterface $dateFrom = null,
        \DateTimeInterface $dateTo = null,
    ) {
        $session = $request->getSession();
        $session->set('mautic.campaign.contact.page', $page);

        $permissions = [
            'campaign:campaigns:view',
            'lead:leads:viewown',
            'lead:leads:viewother',
        ];

        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            $permissions,
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
     * @return JsonResponse|RedirectResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        return $this->deleteStandard($request, $objectId);
    }

    /**
     * @param bool $ignorePost
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        return $this->editStandard($request, $objectId, $ignorePost);
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, $page = null)
    {
        // set some permissions
        $permissions = $this->security->isGranted(
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

        $session = $request->getSession();
        if (empty($page)) {
            $page = $session->get('mautic.campaign.page', 1);
        }

        $limit = $session->get('mautic.campaign.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.campaign.filter', ''));
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
            // the number of entities are now less then the current page so redirect to the last page
            $lastPage = (1 === $count) ? 1 : (((ceil($count / $limit)) ?: 1) ?: 1);

            $session->set('mautic.campaign.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_campaign_index', ['page' => $lastPage]);

            return $this->postActionRedirect(
                $this->getPostActionRedirectArguments(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => ['page' => $lastPage],
                        'contentTemplate' => 'Mautic\CampaignBundle\Controller\CampaignController::indexAction',
                        'passthroughVars' => [
                            'mauticContent' => 'campaign',
                        ],
                    ],
                    'index'
                )
            );
        }

        // set what page currently on so that we can return here after form submission/cancellation
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
            'tmpl'            => $request->get('tmpl', 'index'),
        ];

        return $this->delegateView(
            $this->getViewArguments(
                [
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => '@MauticCampaign/Campaign/list.html.twig',
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
     * @return RedirectResponse|Response
     */
    public function newAction(Request $request)
    {
        /** @var CampaignModel $model */
        $model    = $this->getModel('campaign');
        $campaign = $model->getEntity();

        if (!$this->security->isGranted('campaign:campaigns:create')) {
            return $this->accessDenied();
        }

        // set the page we came from
        $page = $request->getSession()->get('mautic.campaign.page', 1);

        $options = $this->getEntityFormOptions();
        $action  = $this->generateUrl('mautic_campaign_action', ['objectAction' => 'new']);
        $form    = $model->createForm($campaign, $this->formFactory, $action, $options);

        // /Check for a submitted form and process it
        $isPost = 'POST' === $request->getMethod();
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
                            $template       = 'Mautic\CampaignBundle\Controller\CampaignController::viewAction';
                        } else {
                            $viewParameters = ['page' => $page];
                            $returnUrl      = $this->generateUrl('mautic_campaign_index', $viewParameters);
                            $template       = 'Mautic\CampaignBundle\Controller\CampaignController::indexAction';
                        }
                    }
                }

                $this->afterFormProcessed($valid, $campaign, $form, 'new');
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl($this->getIndexRoute(), $viewParameters);
                $template       = 'Mautic\CampaignBundle\Controller\CampaignController::indexAction';
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
                return $this->editAction($request, $campaign->getId(), true);
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
                'tmpl'            => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'entity'          => $campaign,
                'form'            => $this->getFormView($form, 'new'),
            ],
            'contentTemplate' => '@MauticCampaign/Campaign/form.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'campaign',
                'route'         => $this->generateUrl(
                    'mautic_campaign_action',
                    [
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), // valid means a new form was applied
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
     * @return JsonResponse|Response
     */
    public function viewAction(Request $request, $objectId)
    {
        return $this->viewStandard($request, $objectId, $this->getModelName(), null, null, 'campaign');
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
    protected function afterEntitySave($entity, FormInterface $form, $action, $persistConnections = null)
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
     * @param bool $isClone
     */
    protected function afterFormProcessed($isValid, $entity, FormInterface $form, $action, $isClone = false)
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
     * @param bool $isClone
     */
    protected function beforeFormProcessed($entity, FormInterface $form, $action, $isPost, $objectId = null, $isClone = false)
    {
        $sessionId = $this->getCampaignSessionId($entity, $action, $objectId);
        // set added/updated events
        [$this->modifiedEvents, $this->deletedEvents, $this->campaignEvents] = $this->getSessionEvents($sessionId);

        // set added/updated sources
        [$this->addedSources, $this->deletedSources, $campaignSources]     = $this->getSessionSources($sessionId, $isClone);
        $this->connections                                                 = $this->getSessionCanvasSettings($sessionId);

        if ($isPost) {
            $this->getCampaignModel()->setCanvasSettings($entity, $this->connections, false, $this->modifiedEvents);
            $this->prepareCampaignSourcesForEdit($sessionId, $campaignSources, true);
        } else {
            if (!$isClone) {
                // clear out existing fields in case the form was refreshed, browser closed, etc
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
     * @param bool     $isClone
     */
    protected function beforeEntitySave($entity, FormInterface $form, $action, $objectId = null, $isClone = false): bool
    {
        if (empty($this->campaignEvents)) {
            // set the error
            $form->addError(
                new FormError(
                    $this->translator->trans('mautic.campaign.form.events.notempty', [], 'validators')
                )
            );

            return false;
        }

        if (empty($this->campaignSources['lists']) && empty($this->campaignSources['forms'])) {
            // set the error
            $form->addError(
                new FormError(
                    $this->translator->trans('mautic.campaign.form.sources.notempty', [], 'validators')
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
     */
    protected function clearSessionComponents($id)
    {
        $session = $this->getCurrentRequest()->getSession();
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
            if ($this->requestStack->getCurrentRequest()->request->has('campaign')) {
                $campaign  = $this->requestStack->getCurrentRequest()->request->all()['campaign'] ?? [];
                $sessionId = $campaign['sessionId'] ?? $sessionId;
            }
        } elseif ('edit' === $action) {
            $sessionId = $campaign->getId();
        }

        $this->sessionId = $sessionId;

        return $sessionId;
    }

    protected function getTemplateBase(): string
    {
        return '@MauticCampaign/Campaign';
    }

    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = [])
    {
        $session        = $this->getCurrentRequest()->getSession();
        $currentFilters = $session->get('mautic.campaign.list_filters', []);
        $updatedFilters = $this->requestStack->getCurrentRequest()->get('filters', false);

        $sourceLists = $this->getCampaignModel()->getSourceLists();
        $listFilters = [
            'filters' => [
                'placeholder' => $this->translator->trans('mautic.campaign.filter.placeholder'),
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

    protected function getModelName(): string
    {
        return 'campaign';
    }

    /**
     * @return mixed[]
     */
    protected function getPostActionRedirectArguments(array $args, $action): array
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
     */
    protected function getSessionEvents($id): array
    {
        $session = $this->getCurrentRequest()->getSession();

        $modifiedEvents = $session->get('mautic.campaign.'.$id.'.events.modified', []);
        $deletedEvents  = $session->get('mautic.campaign.'.$id.'.events.deleted', []);

        $events = array_diff_key($modifiedEvents, array_flip($deletedEvents));

        return [$modifiedEvents, $deletedEvents, $events];
    }

    /**
     * Get events from session.
     */
    protected function getSessionSources($id, $isClone = false): array
    {
        $session = $this->getCurrentRequest()->getSession();

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
        switch ($action) {
            case 'index':
                $args['viewParameters']['filters'] = $this->listFilters;
                break;
            case 'view':
                /** @var Campaign $entity */
                $entity   = $args['entity'];
                $objectId = $args['objectId'];
                // Init the date range filter form
                $dateRangeValues = $this->requestStack->getCurrentRequest()->get('daterange', []);
                $action          = $this->generateUrl('mautic_campaign_action', ['objectAction' => 'view', 'objectId' => $objectId]);
                $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
                $events          = $this->getCampaignModel()->getEventRepository()->getCampaignEvents($entity->getId());
                $dateFrom        = null;
                $dateTo          = null;
                $dateToPlusOne   = null;

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

                $isEmailStatsEnabled = (bool) $this->coreParametersHelper->get('campaign_email_stats_enabled', true);
                $showEmailStats      = $isEmailStatsEnabled && $entity->isEmailCampaign();

                $args['viewParameters'] = array_merge(
                    $args['viewParameters'],
                    [
                        'campaign'        => $entity,
                        'stats'           => $stats,
                        'events'          => $sortedEvents,
                        'eventSettings'   => $this->eventCollector->getEventsArray(),
                        'sources'         => $this->getCampaignModel()->getLeadSources($entity),
                        'dateRangeForm'   => $dateRangeForm->createView(),
                        'campaignSources' => $this->campaignSources,
                        'campaignEvents'  => $events,
                        'showEmailStats'  => $showEmailStats,
                    ]
                );
                break;
            case 'new':
            case 'edit':
                $session                = $this->getCurrentRequest()->getSession();
                $args['viewParameters'] = array_merge(
                    $args['viewParameters'],
                    [
                        'eventSettings'   => $this->eventCollector->getEventsArray(),
                        'campaignEvents'  => $this->campaignEvents,
                        'campaignSources' => $this->campaignSources,
                        'deletedEvents'   => $this->deletedEvents,
                        'hasEventClone'   => $session->has('mautic.campaign.events.clone.storage'),
                    ]
                );
                break;
        }

        return $args;
    }

    /**
     * @param bool $isClone
     *
     * @return array
     */
    protected function prepareCampaignEventsForEdit($entity, $objectId, $isClone = false)
    {
        // load existing events into session
        $campaignEvents = [];

        $existingEvents = $entity->getEvents()->toArray();
        $translator     = $this->translator;
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
                            '%full%' => $this->dateHelper->toFull($event['triggerDate']),
                            '%time%' => $this->dateHelper->toTime($event['triggerDate']),
                            '%date%' => $this->dateHelper->toShort($event['triggerDate']),
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
        $this->getCurrentRequest()->getSession()->set('mautic.campaign.'.$objectId.'.events.modified', $campaignEvents);
    }

    protected function prepareCampaignSourcesForEdit($objectId, $campaignSources, $isPost = false)
    {
        $this->campaignSources = [];
        if (is_array($campaignSources)) {
            foreach ($campaignSources as $type => $sources) {
                if (!empty($sources)) {
                    $campaignModel = $this->getModel('campaign');
                    \assert($campaignModel instanceof CampaignModel);

                    $sourceList                   = $campaignModel->getSourceLists($type);
                    $this->campaignSources[$type] = [
                        'sourceType' => $type,
                        'campaignId' => $objectId,
                        'names'      => implode(', ', array_intersect_key($sourceList, $sources)),
                    ];
                }
            }
        }

        if (!$isPost) {
            $session = $this->getCurrentRequest()->getSession();
            $session->set('mautic.campaign.'.$objectId.'.leadsources.current', $campaignSources);
            $session->set('mautic.campaign.'.$objectId.'.leadsources.modified', $campaignSources);
        }
    }

    protected function setSessionCanvasSettings($sessionId, $canvasSettings)
    {
        $this->getCurrentRequest()->getSession()->set('mautic.campaign.'.$sessionId.'.events.canvassettings', $canvasSettings);
    }

    protected function getSessionCanvasSettings($sessionId): mixed
    {
        return $this->getCurrentRequest()->getSession()->get('mautic.campaign.'.$sessionId.'.events.canvassettings');
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
            $summaryRepo                = $this->doctrine->getManager()->getRepository(Summary::class);
            $campaignLogCounts          = $summaryRepo->getCampaignLogCounts($id, $dateFrom, $dateToPlusOne);
            $campaignLogCountsProcessed = $this->getCampaignLogCountsProcessed($campaignLogCounts);
        } else {
            /** @var LeadEventLogRepository $eventLogRepo */
            $eventLogRepo               = $this->doctrine->getManager()->getRepository(LeadEventLog::class);
            $campaignLogCounts          = $eventLogRepo->getCampaignLogCounts($id, false, false, false, $dateFrom, $dateToPlusOne);
            $campaignLogCountsProcessed = $eventLogRepo->getCampaignLogCounts($id, true, false, false, $dateFrom, $dateToPlusOne);
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
        array $campaignLogCountsProcessed,
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
            if (!empty($event['decisionPath'])
                && !empty($event['parent_id'])
                && isset($events[$event['parent_id']])
                && 'condition' !== $event['eventType']) {
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
