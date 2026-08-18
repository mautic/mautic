<?php

namespace Mautic\LeadBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\FrequencyRuleTrait;
use Mautic\LeadBundle\Controller\LeadDetailsTrait;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DeviceModel;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\NoteModel;
use Mautic\StageBundle\Model\StageModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Lead>
 */
final class LeadApiController extends CommonApiController
{
    use CustomFieldsApiControllerTrait;
    use FrequencyRuleTrait;
    use LeadDetailsTrait;

    /**
     * @var LeadModel|null
     */
    protected $model;

    private DoNotContactModel $doNotContactModel;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        DoNotContactModel $doNotContactModel,
        AppVersion $appVersion,
        private readonly ContactMerger $contactMerger,
        private readonly UserHelper $userHelper,
        private readonly IpLookupHelper $ipLookupHelper,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        private readonly CampaignModel $campaignModel,
        private readonly FieldModel $leadFieldModel,
        LeadModel $leadModel,
        private readonly StageModel $stageModel,
        private readonly UserModel $userModel,
        private readonly DeviceModel $deviceModel,
        private readonly NoteModel $noteModel,
    ) {
        $this->doNotContactModel = $doNotContactModel;

        $this->model            = $leadModel;
        $this->entityClass      = Lead::class;
        $this->entityNameOne    = 'contact';
        $this->entityNameMulti  = 'contacts';
        $this->serializerGroups = ['leadDetails', 'frequencyRulesList', 'doNotContactList', 'userList', 'stageList', 'publishDetails', 'ipAddress', 'tagList', 'utmtagsList'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Obtains a list of users for lead owner edits.
     */
    public function getOwnersAction(Request $request): Response
    {
        if (!$this->security->isGranted(
            ['lead:leads:create', 'lead:leads:editown', 'lead:leads:editother'],
            'MATCH_ONE'
        )
        ) {
            return $this->accessDenied();
        }

        $filter  = $request->query->get('filter');
        $limit   = $request->query->get('limit');
        $start   = $request->query->get('start');
        $users   = $this->model->getLookupResults('user', $filter, $limit, $start);
        $view    = $this->view($users, Response::HTTP_OK);
        $context = $view->getContext()->setGroups(['userList']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    protected function getTotalCountTtl(): ?int
    {
        return $this->coreParametersHelper->get('contact_api_count_cache_ttl', 5);
    }

    /**
     * Obtains a list of custom fields.
     */
    public function getFieldsAction(): Response
    {
        if (!$this->security->isGranted(['lead:leads:editown', 'lead:leads:editother'], 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        $fields = $this->leadFieldModel->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'f.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                            'object' => 'lead',
                        ],
                    ],
                ],
            ]
        );

        $view    = $this->view($fields, Response::HTTP_OK);
        $context = $view->getContext()->setGroups(['leadFieldList']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of notes on a specific lead.
     */
    public function getNotesAction(Request $request, $id): Response
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->security->hasEntityAccess('lead:notes:viewown', 'lead:notes:viewother', $entity->getPermissionUser())) {
            return $this->accessDenied();
        }

        $defaultPageLimit = (int) $this->coreParametersHelper->get('default_pagelimit');

        $results = $this->noteModel->getEntities(
            [
                'start'  => $request->query->get('start', '0'),
                'limit'  => $request->query->getInt('limit', $defaultPageLimit),
                'filter' => [
                    'string' => $request->query->get('search', ''),
                    'force'  => [
                        [
                            'column' => 'n.lead',
                            'expr'   => 'eq',
                            'value'  => $entity,
                        ],
                    ],
                ],
                'orderBy'    => $request->query->get('orderBy', 'n.dateAdded'),
                'orderByDir' => $request->query->get('orderByDir', 'DESC'),
            ]
        );

        [$notes, $count] = $this->prepareEntitiesForView($results);

        $view = $this->view(
            [
                'total' => $count,
                'notes' => $notes,
            ],
            Response::HTTP_OK
        );

        $context = $view->getContext()->setGroups(['leadNoteDetails']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of devices on a specific lead.
     */
    public function getDevicesAction(Request $request, $id): Response
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
            return $this->accessDenied();
        }

        $defaultPagelimit = (int) $this->coreParametersHelper->get('default_pagelimit');

        $results = $this->deviceModel->getEntities(
            [
                'start'  => $request->query->get('start', '0'),
                'limit'  => $request->query->getInt('limit', $defaultPagelimit),
                'filter' => [
                    'string' => $request->query->get('search', ''),
                    'force'  => [
                        [
                            'column' => 'd.lead',
                            'expr'   => 'eq',
                            'value'  => $entity,
                        ],
                    ],
                ],
                'orderBy'    => $request->query->get('orderBy', 'd.dateAdded'),
                'orderByDir' => $request->query->get('orderByDir', 'DESC'),
            ]
        );

        [$devices, $count] = $this->prepareEntitiesForView($results);

        $view = $this->view(
            [
                'total'   => $count,
                'devices' => $devices,
            ],
            Response::HTTP_OK
        );

        $context = $view->getContext()->setGroups(['leadDeviceDetails']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of contact segments the contact is in.
     */
    public function getListsAction($id): Response
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
                return $this->accessDenied();
            }

            $lists = $this->model->getLists($entity, true, true);

            foreach ($lists as &$l) {
                unset($l['leads'][0]['leadlist_id']);
                unset($l['leads'][0]['lead_id']);

                $l = array_merge($l, $l['leads'][0]);

                unset($l['leads']);
            }

            $view = $this->view(
                [
                    'total' => count($lists),
                    'lists' => $lists,
                ],
                Response::HTTP_OK
            );

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Obtains a list of contact companies the contact is in.
     */
    public function getCompaniesAction($id): Response
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
            return $this->accessDenied();
        }

        $companies = $this->model->getCompanies($entity);

        $view = $this->view(
            [
                'total'     => count($companies),
                'companies' => $companies,
            ],
            Response::HTTP_OK
        );

        return $this->handleView($view);
    }

    /**
     * Obtains a list of campaigns the lead is part of.
     */
    public function getCampaignsAction($id): Response
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
                return $this->accessDenied();
            }

            $campaigns = $this->campaignModel->getLeadCampaigns($entity, true);

            foreach ($campaigns as &$c) {
                if (!empty($c['lists'])) {
                    $c['listMembership'] = array_keys($c['lists']);
                    unset($c['lists']);
                }

                unset($c['leads'][0]['campaign_id']);
                unset($c['leads'][0]['lead_id']);

                $c = array_merge($c, $c['leads'][0]);

                unset($c['leads']);
            }

            $view = $this->view(
                [
                    'total'     => count($campaigns),
                    'campaigns' => $campaigns,
                ],
                Response::HTTP_OK
            );

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Obtains a list of contact events.
     */
    public function getActivityAction(Request $request, $id): Response
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity)) {
            return $this->accessDenied();
        }

        return $this->getAllActivityAction($request, $entity);
    }

    /**
     * Obtains a list of contact events.
     */
    public function getAllActivityAction(Request $request, $lead = null): Response
    {
        $canViewOwn    = $this->security->isGranted('lead:leads:viewown');
        $canViewOthers = $this->security->isGranted('lead:leads:viewother');

        if (!$canViewOthers && !$canViewOwn) {
            return $this->accessDenied();
        }

        $filters = $this->sanitizeEventFilter(InputHelper::clean($request->query->all()['filters'] ?? $request->request->all()['filters'] ?? []));
        $limit   = (int) $request->get('limit', 25);
        $page    = (int) $request->get('page', 1);
        $order   = InputHelper::clean($request->get('order', ['timestamp', 'DESC']));

        [$events, $serializerGroups] = $this->model->getEngagements($lead, $filters, $order, $page, $limit, false);

        $view    = $this->view($events);
        $context = $view->getContext()->setGroups($serializerGroups);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Adds a DNC to the contact.
     */
    public function addDncAction(Request $request, $id, $channel): Response
    {
        $entity = $this->model->getEntity((int) $id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        $channelId = (int) $request->request->get('channelId');
        if ($channelId) {
            $channel = [$channel => $channelId];
        }

        // If no reason is set, default to 3 (manual)
        $reason = (int) $request->request->get('reason', DoNotContact::MANUAL);

        // If a reason is set, but it's empty or 0, show an error.
        if (0 === $reason) {
            return $this->returnError(
                'Invalid reason code given',
                Response::HTTP_BAD_REQUEST,
                ['Reason code needs to be an integer and higher than 0.']
            );
        }

        $comments = InputHelper::clean($request->request->get('comments'));

        $this->doNotContactModel->addDncForContact($entity->getId(), $channel, $reason, $comments);
        $view = $this->view([$this->entityNameOne => $entity]);

        return $this->handleView($view);
    }

    /**
     * Removes a DNC from the contact.
     */
    public function removeDncAction($id, $channel): Response
    {
        $entity = $this->model->getEntity((int) $id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        $result = $this->doNotContactModel->removeDncForContact($entity->getId(), $channel);
        $view   = $this->view(
            [
                'recordFound'        => $result,
                $this->entityNameOne => $entity,
            ]
        );

        return $this->handleView($view);
    }

    /**
     * Add/Remove a UTM Tagset to/from the contact.
     *
     * @param int              $id
     * @param string           $method
     * @param array<mixed>|int $data
     */
    protected function applyUtmTagsAction($id, $method, $data): Response
    {
        $entity = $this->model->getEntity((int) $id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        // calls add/remove method as appropriate
        $result = $this->model->{$method}($entity, $data);

        if (false === $result) {
            return $this->badRequest();
        }

        if ('removeUtmTags' == $method) {
            $view = $this->view(
                [
                    'recordFound'        => $result,
                    $this->entityNameOne => $entity,
                ]
            );
        } else {
            $view = $this->view([$this->entityNameOne => $entity]);
        }

        return $this->handleView($view);
    }

    /**
     * Adds a UTM Tagset to the contact.
     *
     * @param int $id
     */
    public function addUtmTagsAction(Request $request, $id): Response
    {
        return $this->applyUtmTagsAction($id, 'addUTMTags', $request->request->all());
    }

    /**
     * Remove a UTM Tagset for the contact.
     *
     * @param int $id
     * @param int $utmid
     */
    public function removeUtmTagsAction($id, $utmid): Response
    {
        return $this->applyUtmTagsAction($id, 'removeUtmTags', (int) $utmid);
    }

    /**
     * Creates new entity from provided params.
     *
     * @return object
     */
    public function getNewEntity(array $params)
    {
        return $this->model->checkForDuplicateContact($params);
    }

    protected function prepareParametersForBinding(Request $request, array $parameters, object $entity, string $action): array
    {
        // Unset the tags from params to avoid a validation error
        if (isset($parameters['tags'])) {
            unset($parameters['tags']);
        }

        // keep existing tags
        foreach ($entity->getTags() as $tag) {
            $parameters['tags'][] = $tag->getId();
        }

        // keep existing owner if it is not set or should be reset to null
        if (!array_key_exists('owner', $parameters) && $entity->getOwner()) {
            $parameters['owner'] = $entity->getOwner()->getId();
        }
        // keep existing stage if it is not set or should be reset to null
        if (!array_key_exists('stage', $parameters) && $entity->getStage()) {
            $parameters['stage'] = $entity->getStage()->getId();
        }

        return $parameters;
    }

    /**
     * @param Lead                 $entity
     * @param FormInterface<mixed> $form
     * @param array<mixed>         $parameters
     * @param string               $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if ('edit' === $action) {
            // Merge existing duplicate contact based on unique fields if exist
            // new endpoints will leverage getNewEntity in order to return the correct status codes
            $existingEntity = $this->model->checkForDuplicateContact($this->entityRequestParameters);
            \assert($existingEntity instanceof Lead);

            if ($entity->getId() && $existingEntity->getId()) {
                try {
                    $entity = $this->contactMerger->merge($entity, $existingEntity);
                } catch (SameContactException) {
                }
            } elseif ($existingEntity->getId()) {
                $entity = $existingEntity;
            }
        }

        $manipulatorObject = $this->inBatchMode ? 'api-batch' : 'api-single';

        $entity->setManipulator(new LeadManipulator(
            'lead',
            $manipulatorObject,
            null,
            $this->userHelper->getUser()->getName()
        ));

        if (isset($parameters['companies'])) {
            $this->model->modifyCompanies($entity, $parameters['companies']);
            unset($parameters['companies']);
        }

        if (isset($parameters['owner'])) {
            $owner = $this->userModel->getEntity((int) $parameters['owner']);
            $entity->setOwner($owner);
            unset($parameters['owner']);
        }

        if (isset($parameters['stage'])) {
            $stage = $this->stageModel->getEntity((int) $parameters['stage']);
            $entity->setStage($stage);
            unset($parameters['stage']);
        }

        if (isset($this->entityRequestParameters['tags'])) {
            $this->model->modifyTags($entity, $this->entityRequestParameters['tags'], null, false);
        }

        // Since the request can be from 3rd party, check for an IP address if included
        if (isset($this->entityRequestParameters['ipAddress'])) {
            $ipAddress = $this->ipLookupHelper->getIpAddress($this->entityRequestParameters['ipAddress']);

            if (!$entity->getIpAddresses()->contains($ipAddress)) {
                $entity->addIpAddress($ipAddress);
            }

            unset($this->entityRequestParameters['ipAddress']);
        }

        // Check for lastActive date
        if (isset($this->entityRequestParameters['lastActive'])) {
            $lastActive = new DateTimeHelper($this->entityRequestParameters['lastActive']);
            $entity->setLastActive($lastActive->getDateTime());
            unset($this->entityRequestParameters['lastActive']);
        }

        // Batch DNC settings
        if (!empty($parameters['doNotContact']) && is_array($parameters['doNotContact'])) {
            foreach ($parameters['doNotContact'] as $dnc) {
                $channel  = !empty($dnc['channel']) ? $dnc['channel'] : 'email';
                $comments = !empty($dnc['comments']) ? $dnc['comments'] : '';

                $reason = (int) ArrayHelper::getValue('reason', $dnc, DoNotContact::MANUAL);

                if (DoNotContact::IS_CONTACTABLE === $reason) {
                    if (!empty($entity->getId())) {
                        // Remove DNC record
                        $this->doNotContactModel->removeDncForContact($entity->getId(), $channel, false);
                    }
                } elseif (empty($entity->getId())) {
                    // Contact doesn't exist yet. Directly create a DNC record on the entity.
                    $this->doNotContactModel->createDncRecord($entity, $channel, $reason, $comments);
                } else {
                    // Add DNC record to existing contact
                    $this->doNotContactModel->addDncForContact($entity->getId(), $channel, $reason, $comments, false);
                }
            }
            unset($parameters['doNotContact']);
        }

        if (!empty($parameters['frequencyRules'])) {
            $viewParameters = [];
            $data           = $this->getFrequencyRuleFormData($entity, null, null, false, $parameters['frequencyRules']);

            if (true !== $frequencyForm = $this->getFrequencyRuleForm($entity, $viewParameters, $data)) {
                $formErrors = $this->getFormErrorMessages($frequencyForm);
                $msg        = $this->getFormErrorMessage($formErrors);

                if (!$msg) {
                    $msg = $this->translator->trans('mautic.core.error.badrequest', [], 'flashes');
                }

                return $this->returnError($msg, Response::HTTP_BAD_REQUEST, $formErrors);
            }

            unset($parameters['frequencyRules']);
        }

        $isPostOrPatch = 'POST' === $this->requestStack->getCurrentRequest()->getMethod() || 'PATCH' === $this->requestStack->getCurrentRequest()->getMethod();
        $this->setCustomFieldValues($entity, $form, $parameters, $isPostOrPatch);
    }

    /**
     * Helper method to be used in FrequencyRuleTrait.
     */
    protected function isFormCancelled(?FormInterface $form = null): bool
    {
        return false;
    }

    /**
     * Helper method to be used in FrequencyRuleTrait.
     */
    protected function isFormValid(FormInterface $form, ?array $data = null): bool
    {
        $form->submit($data, 'PATCH' !== $this->requestStack->getCurrentRequest()->getMethod());

        return $form->isSubmitted() && $form->isValid();
    }

    /**
     * @param Lead $entity
     */
    protected function detachEntity(object $entity): void
    {
        if (empty($entity->getPreviousId())) {
            parent::detachEntity($entity);
        }
    }
}
