<?php

namespace Mautic\LeadBundle\Model;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Form\RequestTrait;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadCategory;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Event\CategoryChangeEvent;
use Mautic\LeadBundle\Event\DoNotContactAddEvent;
use Mautic\LeadBundle\Event\DoNotContactRemoveEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Form\Type\LeadType;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PointBundle\Entity\GroupContactScore;
use Mautic\PointBundle\Entity\GroupContactScoreRepository;
use Mautic\StageBundle\Entity\Stage;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Tightenco\Collect\Support\Collection;

/**
 * @extends FormModel<Lead>
 */
class LeadModel extends FormModel
{
    use DefaultValueTrait;
    use OperatorListTrait;
    use RequestTrait;

    public const CHANNEL_FEATURE = 'contact_preference';

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var array
     */
    protected $leadFields = [];

    protected $leadTrackingId;

    /**
     * @var bool
     */
    protected $leadTrackingCookieGenerated = false;

    /**
     * @var array
     */
    protected $availableLeadFields = [];

    private bool $repoSetup = false;

    private array $flattenedFields = [];

    private array $fieldsByGroup = [];

    public function __construct(
        protected RequestStack $requestStack,
        protected IpLookupHelper $ipLookupHelper,
        protected PathsHelper $pathsHelper,
        protected IntegrationHelper $integrationHelper,
        FieldModel $leadFieldModel,
        protected ListModel $leadListModel,
        protected FormFactoryInterface $formFactory,
        protected CompanyModel $companyModel,
        protected CategoryModel $categoryModel,
        protected ChannelListHelper $channelListHelper,
        CoreParametersHelper $coreParametersHelper,
        protected EmailValidator $emailValidator,
        protected UserProvider $userProvider,
        private ContactTracker $contactTracker,
        private DeviceTracker $deviceTracker,
        private IpAddressModel $ipAddressModel,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger
    ) {
        $this->leadFieldModel       = $leadFieldModel;

        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return LeadRepository
     */
    public function getRepository()
    {
        /** @var LeadRepository $repo */
        $repo = $this->em->getRepository(Lead::class);
        $repo->setDispatcher($this->dispatcher);

        if (!$this->repoSetup) {
            $this->repoSetup = true;

            // set the point trigger model in order to get the color code for the lead
            $fields = $this->leadFieldModel->getFieldList(true, false);

            $socialFields = (!empty($fields['social'])) ? array_keys($fields['social']) : [];
            $repo->setAvailableSocialFields($socialFields);

            $searchFields = [];
            foreach ($fields as $groupFields) {
                $searchFields = array_merge($searchFields, array_keys($groupFields));
            }
            $repo->setAvailableSearchFields($searchFields);
        }

        return $repo;
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\TagRepository
     */
    public function getTagRepository()
    {
        return $this->em->getRepository(Tag::class);
    }

    /**
     * @return \Mautic\LeadBundle\Entity\PointsChangeLogRepository
     */
    public function getPointLogRepository()
    {
        return $this->em->getRepository(PointsChangeLog::class);
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\UtmTagRepository
     */
    public function getUtmTagRepository()
    {
        return $this->em->getRepository(UtmTag::class);
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadDeviceRepository
     */
    public function getDeviceRepository()
    {
        return $this->em->getRepository(\Mautic\LeadBundle\Entity\LeadDevice::class);
    }

    /**
     * Get the lead event log repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadEventLogRepository
     */
    public function getEventLogRepository()
    {
        return $this->em->getRepository(LeadEventLog::class);
    }

    /**
     * Get the frequency rules repository.
     *
     * @return \Mautic\LeadBundle\Entity\FrequencyRuleRepository
     */
    public function getFrequencyRuleRepository()
    {
        return $this->em->getRepository(FrequencyRule::class);
    }

    /**
     * Get the Stages change log repository.
     *
     * @return \Mautic\LeadBundle\Entity\StagesChangeLogRepository
     */
    public function getStagesChangeLogRepository()
    {
        return $this->em->getRepository(StagesChangeLog::class);
    }

    /**
     * Get the lead categories repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadCategoryRepository
     */
    public function getLeadCategoryRepository()
    {
        return $this->em->getRepository(LeadCategory::class);
    }

    /**
     * @return \Mautic\LeadBundle\Entity\MergeRecordRepository
     */
    public function getMergeRecordRepository()
    {
        return $this->em->getRepository(\Mautic\LeadBundle\Entity\MergeRecord::class);
    }

    /**
     * @return \Mautic\LeadBundle\Entity\LeadListRepository
     */
    public function getLeadListRepository()
    {
        return $this->em->getRepository(LeadList::class);
    }

    public function getGroupContactScoreRepository(): GroupContactScoreRepository
    {
        return $this->em->getRepository(GroupContactScore::class);
    }

    public function getPermissionBase(): string
    {
        return 'lead:leads';
    }

    public function getNameGetter(): string
    {
        return 'getPrimaryIdentifier';
    }

    /**
     * @param Lead        $entity
     * @param string|null $action
     * @param array       $options
     *
     * @return \Symfony\Component\Form\FormInterface<Lead>
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(['Lead'], 'Entity must be of class Lead()');
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(LeadType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Lead
    {
        if (null === $id) {
            return new Lead();
        }

        $entity = parent::getEntity($id);

        if (null === $entity) {
            // Check if this contact was merged into another and if so, return the new contact
            if ($entity = $this->getMergeRecordRepository()->findMergedContact($id)) {
                // Hydrate fields with custom field data
                $fields = $this->getRepository()->getFieldValues($entity->getId());
                $entity->setFields($fields);
            }
        }

        return $entity;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(['Lead'], 'Entity must be of class Lead()');
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::LEAD_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::LEAD_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::LEAD_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::LEAD_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param Lead $entity
     * @param bool $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        $companyFieldMatches = [];
        $fields              = $entity->getFields();
        $company             = null;

        // check to see if we can glean information from ip address
        if (!$entity->imported && count($ips = $entity->getIpAddresses())) {
            $details = $ips->first()->getIpDetails();
            // Only update with IP details if none of the following are set to prevent wrong combinations
            if (empty($fields['core']['city']['value']) && empty($fields['core']['state']['value']) && empty($fields['core']['country']['value']) && empty($fields['core']['zipcode']['value'])) {
                if ($this->coreParametersHelper->get('anonymize_ip') && $this->ipLookupHelper->getRealIp()) {
                    $details = $this->ipLookupHelper->getIpDetails($this->ipLookupHelper->getRealIp());
                }

                if (!empty($details['city'])) {
                    $entity->addUpdatedField('city', $details['city']);
                    $companyFieldMatches['city'] = $details['city'];
                }

                if (!empty($details['region'])) {
                    $entity->addUpdatedField('state', $details['region']);
                    $companyFieldMatches['state'] = $details['region'];
                }

                if (!empty($details['country'])) {
                    $entity->addUpdatedField('country', $details['country']);
                    $companyFieldMatches['country'] = $details['country'];
                }

                if (!empty($details['zipcode'])) {
                    $entity->addUpdatedField('zipcode', $details['zipcode']);
                }
            }

            if (!$entity->getCompany() && !empty($details['organization']) && $this->coreParametersHelper->get('ip_lookup_create_organization', false)) {
                $entity->addUpdatedField('company', $details['organization']);
            }
        }

        $updatedFields   = $entity->getUpdatedFields();
        $changeLogEntity = null;
        if (isset($updatedFields['company'])) {
            $companyFieldMatches['company']            = $updatedFields['company'];
            [$company, $leadAdded, $companyEntity]     = IdentifyCompanyHelper::identifyLeadsCompany($companyFieldMatches, $entity, $this->companyModel);
            if ($leadAdded) {
                $changeLogEntity = $entity->addCompanyChangeLogEntry('form', 'Identify Company', 'Lead added to the company, '.$company['companyname'], $company['id']);
            }
        }

        $this->validateSelectFields($entity, $fields);

        $this->processManipulator($entity);

        $this->setEntityDefaultValues($entity);

        $this->ipAddressModel->saveIpAddressesReferencesForContact($entity);

        parent::saveEntity($entity, $unlock);

        if (!empty($company)) {
            // Save after the lead in for new leads created through the API and maybe other places
            $this->companyModel->addLeadToCompany($companyEntity, $entity);
            $this->setPrimaryCompany($companyEntity->getId(), $entity->getId());
        } elseif (array_key_exists('company', $updatedFields) && empty($updatedFields['company'])) {
            $this->companyModel->getCompanyLeadRepository()->removeContactPrimaryCompany($entity->getId());
        }

        if (null !== $changeLogEntity) {
            $this->em->detach($changeLogEntity);
        }
    }

    /**
     * @param object $entity
     */
    public function deleteEntity($entity): void
    {
        // Delete custom avatar if one exists
        $imageDir = $this->pathsHelper->getSystemPath('images', true);
        $avatar   = $imageDir.'/lead_avatars/avatar'.$entity->getId();

        if (file_exists($avatar)) {
            unlink($avatar);
        }

        parent::deleteEntity($entity);
    }

    /**
     * Populates custom field values for updating the lead. Also retrieves social media data.
     *
     * @param bool|false $overwriteWithBlank
     * @param bool|true  $fetchSocialProfiles
     * @param bool|false $bindWithForm        Send $data through the Lead form and only use valid data (should be used with request data)
     *
     * @throws ImportFailedException
     */
    public function setFieldValues(Lead $lead, array $data, $overwriteWithBlank = false, $fetchSocialProfiles = true, $bindWithForm = false): void
    {
        if ($fetchSocialProfiles) {
            // @todo - add a catch to NOT do social gleaning if a lead is created via a form, etc as we do not want the user to experience the wait
            // generate the social cache
            [$socialCache, $socialFeatureSettings] = $this->integrationHelper->getUserProfiles(
                $lead,
                $data,
                true,
                null,
                false,
                true
            );

            // set the social cache while we have it
            if (!empty($socialCache)) {
                $lead->setSocialCache($socialCache);
            }
        }

        if (isset($data['stage'])) {
            $stagesChangeLogRepo  = $this->getStagesChangeLogRepository();
            $currentLeadStageId   = $stagesChangeLogRepo->getCurrentLeadStage($lead->getId());
            $currentLeadStageName = null;
            if ($currentLeadStageId) {
                /** @var Stage|null $currentStage */
                $currentStage = $this->em->getRepository(Stage::class)->findByIdOrName($currentLeadStageId);
                if ($currentStage) {
                    $currentLeadStageName = $currentStage->getName();
                }
            }

            $newLeadStageIdOrName = is_object($data['stage']) ? $data['stage']->getId() : $data['stage'];
            if ((int) $newLeadStageIdOrName !== $currentLeadStageId && $newLeadStageIdOrName !== $currentLeadStageName) {
                /** @var Stage|null $newStage */
                $newStage = $this->em->getRepository(Stage::class)->findByIdOrName($newLeadStageIdOrName);
                if ($newStage) {
                    $lead->stageChangeLogEntry(
                        $newStage,
                        $newStage->getId().':'.$newStage->getName(),
                        $this->translator->trans('mautic.stage.event.changed')
                    );
                } else {
                    throw new ImportFailedException($this->translator->trans('mautic.lead.import.stage.not.exists', ['id' => $newLeadStageIdOrName]));
                }
            }
        }

        // save the field values
        $fieldValues = $lead->getFields();

        if (empty($fieldValues) || $bindWithForm) {
            // Lead is new or they haven't been populated so let's build the fields now
            if (empty($this->flattenedFields)) {
                /** @var Paginator<mixed[]> $paginator */
                $paginator = $this->leadFieldModel->getEntities(
                    [
                        'filter'         => ['isPublished' => true, 'object' => 'lead'],
                        'hydration_mode' => 'HYDRATE_ARRAY',
                        'result_cache'   => new ResultCacheOptions(LeadField::CACHE_NAMESPACE),
                    ]
                );
                $this->flattenedFields = iterator_to_array($paginator->getIterator());
                $this->fieldsByGroup   = $this->organizeFieldsByGroup($this->flattenedFields);
            }

            if (empty($fieldValues)) {
                $fieldValues = $this->fieldsByGroup;
            }
        }

        if ($bindWithForm) {
            // Cleanup the field values
            $form = $this->createForm(
                new Lead(), // use empty lead to prevent binding errors
                $this->formFactory,
                null,
                ['fields' => $this->flattenedFields, 'csrf_protection' => false, 'allow_extra_fields' => true]
            );

            // Unset stage and owner from the form because it's already been handled
            unset($data['stage'], $data['owner'], $data['tags']);
            // Prepare special fields
            $this->prepareParametersFromRequest($form, $data, $lead, [], $this->fieldsByGroup);
            // Submit the data
            $form->submit($data);

            if ($form->getErrors()->count()) {
                $this->logger->debug('LEAD: form validation failed with an error of '.$form->getErrors());
            }
            foreach ($form as $field => $formField) {
                if (isset($data[$field])) {
                    if ($formField->getErrors()->count()) {
                        $this->logger->debug('LEAD: '.$field.' failed form validation with an error of '.$formField->getErrors());
                        // Don't save bad data
                        unset($data[$field]);
                    } else {
                        $data[$field] = $formField->getData();
                    }
                }
            }
        }

        // update existing values
        foreach ($fieldValues as $group => &$groupFields) {
            if ('all' === $group) {
                continue;
            }

            foreach ($groupFields as $alias => &$field) {
                if (!isset($field['value'])) {
                    $field['value'] = null;
                }

                // Only update fields that are part of the passed $data array
                if (array_key_exists($alias, $data)) {
                    if (!$bindWithForm) {
                        $this->cleanFields($data, $field);
                    }
                    $curValue = $field['value'];
                    $newValue = $data[$alias] ?? '';

                    if (is_array($newValue)) {
                        $newValue = implode('|', $newValue);
                    }

                    $isEmpty = (null == $newValue || '' == $newValue);
                    if ($curValue !== $newValue && (!$isEmpty || ($isEmpty && $overwriteWithBlank))) {
                        $field['value'] = $newValue;
                        $lead->addUpdatedField($alias, $newValue, $curValue);
                    }

                    // if empty, check for social media data to plug the hole
                    if (empty($newValue) && !empty($socialCache)) {
                        foreach ($socialCache as $service => $details) {
                            // check to see if a field has been assigned

                            if (!empty($socialFeatureSettings[$service]['leadFields'])
                                && in_array($field['alias'], $socialFeatureSettings[$service]['leadFields'])
                            ) {
                                // check to see if the data is available
                                $key = array_search($field['alias'], $socialFeatureSettings[$service]['leadFields']);
                                if (isset($details['profile'][$key])) {
                                    // Found!!
                                    $field['value'] = $details['profile'][$key];
                                    $lead->addUpdatedField($alias, $details['profile'][$key]);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $lead->setFields($fieldValues);
    }

    /**
     * Disassociates a user from leads.
     */
    public function disassociateOwner($userId): void
    {
        $leads = $this->getRepository()->findByOwner($userId);
        foreach ($leads as $lead) {
            $lead->setOwner(null);
            $this->saveEntity($lead);
        }
    }

    /**
     * Get list of entities for autopopulate fields.
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0)
    {
        $results    = [];

        switch ($type) {
            case 'user':
                $results = $this->em->getRepository(User::class)->getUserList($filter, $limit, $start, ['lead' => 'leads']);
                break;
            case 'contact':
                $fetchResults = $this->getEntities(['start' => $start, 'limit' => $limit, 'filter' => $filter]);

                $results = [];

                /** @var Lead $fetchResult */
                foreach ($fetchResults as $fetchResult) {
                    $results[] = [
                        'value' => $fetchResult->getName() ?: $fetchResult->getEmail(),
                        'id'    => $fetchResult->getId(),
                    ];
                }

                break;
        }

        return $results;
    }

    /**
     * Obtain an array of users for api lead edits.
     *
     * @return array<mixed>
     */
    public function getOwnerList()
    {
        return $this->em->getRepository(User::class)->getUserList('', 0);
    }

    /**
     * Obtains a list of leads based off IP.
     *
     * @return array<mixed>
     */
    public function getLeadsByIp($ip)
    {
        return $this->getRepository()->getLeadsByIp($ip);
    }

    /**
     * Obtains a list of leads based a list of IDs.
     *
     * @return Paginator
     */
    public function getLeadsByIds(array $ids)
    {
        return $this->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => $ids,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return bool
     */
    public function canEditContact(Lead $contact)
    {
        return $this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser());
    }

    /**
     * Gets the details of a lead if not already set.
     *
     * @return array<mixed>
     */
    public function getLeadDetails($lead)
    {
        if ($lead instanceof Lead) {
            $fields = $lead->getFields();
            if (!empty($fields)) {
                return $fields;
            }
        }

        $leadId = ($lead instanceof Lead) ? $lead->getId() : (int) $lead;

        return $this->getRepository()->getFieldValues($leadId);
    }

    /**
     * Reorganizes a field list to be keyed by field's group then alias.
     */
    public function organizeFieldsByGroup($fields): array
    {
        $array = [];

        foreach ($fields as $field) {
            if ($field instanceof LeadField) {
                $alias = $field->getAlias();
                if ($field->isPublished() and 'Lead' === $field->getObject()) {
                    $group                                = $field->getGroup();
                    $array[$group][$alias]['id']          = $field->getId();
                    $array[$group][$alias]['group']       = $group;
                    $array[$group][$alias]['label']       = $field->getLabel();
                    $array[$group][$alias]['alias']       = $alias;
                    $array[$group][$alias]['type']        = $field->getType();
                    $array[$group][$alias]['properties']  = $field->getProperties();
                }
            } else {
                $alias = $field['alias'];
                if ($field['isPublished'] and 'lead' === $field['object']) {
                    $group                                = $field['group'];
                    $array[$group][$alias]['id']          = $field['id'];
                    $array[$group][$alias]['group']       = $group;
                    $array[$group][$alias]['label']       = $field['label'];
                    $array[$group][$alias]['alias']       = $alias;
                    $array[$group][$alias]['type']        = $field['type'];
                    $array[$group][$alias]['properties']  = $field['properties'] ?? [];
                }
            }
        }

        // make sure each group key is present
        $groups = ['core', 'social', 'personal', 'professional'];
        foreach ($groups as $g) {
            if (!isset($array[$g])) {
                $array[$g] = [];
            }
        }

        return $array;
    }

    /**
     * Returns flat array for single lead.
     *
     * @return array
     */
    public function getLead($leadId)
    {
        return $this->getRepository()->getLead($leadId);
    }

    /**
     * @param bool $returnWithQueryFields
     *
     * @return array|Lead
     */
    public function checkForDuplicateContact(array $queryFields, $returnWithQueryFields = false, $onlyPubliclyUpdateable = false)
    {
        // Search for lead by request and/or update lead fields if some data were sent in the URL query
        if (empty($this->availableLeadFields)) {
            $filter = ['isPublished' => true, 'object' => 'lead'];

            if ($onlyPubliclyUpdateable) {
                $filter['isPubliclyUpdatable'] = true;
            }

            $this->availableLeadFields = $this->leadFieldModel->getFieldList(
                false,
                false,
                $filter
            );
        }

        $lead            = new Lead();
        $uniqueFields    = $this->leadFieldModel->getUniqueIdentifierFields();
        $uniqueFieldData = [];
        $inQuery         = array_intersect_key($queryFields, $this->availableLeadFields);
        $values          = $onlyPubliclyUpdateable ? $inQuery : $queryFields;

        // Run values through setFieldValues to clean them first
        $this->setFieldValues($lead, $values, false, false);
        $cleanFields = $lead->getFields();

        foreach ($inQuery as $k => $v) {
            if (empty($queryFields[$k])) {
                unset($inQuery[$k]);
            }
        }

        foreach ($cleanFields as $group) {
            foreach ($group as $key => $field) {
                if (array_key_exists($key, $uniqueFields) && !empty($field['value'])) {
                    $uniqueFieldData[$key] = $field['value'];
                }
            }
        }

        // Check for leads using unique identifier
        if (count($uniqueFieldData)) {
            $existingLeads = $this->getRepository()->getLeadsByUniqueFields($uniqueFieldData);

            if (!empty($existingLeads)) {
                $this->logger->debug("LEAD: Existing contact ID# {$existingLeads[0]->getId()} found through query identifiers.");
                $lead = $existingLeads[0];
            }
        }

        return $returnWithQueryFields ? [$lead, $inQuery] : $lead;
    }

    /**
     * Get a list of segments this lead belongs to.
     *
     * @param bool $forLists
     * @param bool $arrayHydration
     * @param bool $isPublic
     *
     * @return mixed
     */
    public function getLists(Lead $lead, $forLists = false, $arrayHydration = false, $isPublic = false, $isPreferenceCenter = false)
    {
        $repo = $this->em->getRepository(LeadList::class);

        return $repo->getLeadLists($lead->getId(), $forLists, $arrayHydration, $isPublic, $isPreferenceCenter);
    }

    /**
     * Get a list of companies this contact belongs to.
     *
     * @return array<mixed>
     */
    public function getCompanies(Lead $lead)
    {
        $repo = $this->em->getRepository(CompanyLead::class);

        return $repo->getCompaniesByLeadId($lead->getId());
    }

    /**
     * Add lead to lists.
     *
     * @param array|Lead|int $lead
     * @param array|LeadList $lists
     * @param bool           $manuallyAdded
     */
    public function addToLists($lead, $lists, $manuallyAdded = true): void
    {
        $this->leadListModel->addLead($lead, $lists, $manuallyAdded);
    }

    /**
     * Remove lead from lists.
     *
     * @param bool $manuallyRemoved
     */
    public function removeFromLists($lead, $lists, $manuallyRemoved = true): void
    {
        $this->leadListModel->removeLead($lead, $lists, $manuallyRemoved);
    }

    /**
     * Add lead to Stage.
     *
     * @param array|Lead  $lead
     * @param array|Stage $stage
     * @param bool        $manuallyAdded
     *
     * @return $this
     */
    public function addToStages($lead, $stage, $manuallyAdded = true)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference(Lead::class, $leadId);
        }
        $lead->setStage($stage);
        $lead->stageChangeLogEntry(
            $stage,
            $stage->getId().': '.$stage->getName(),
            $this->translator->trans('mautic.stage.event.added.batch')
        );

        return $this;
    }

    /**
     * Remove lead from Stage.
     *
     * @param bool $manuallyRemoved
     *
     * @return $this
     */
    public function removeFromStages($lead, $stage, $manuallyRemoved = true)
    {
        $lead->setStage(null);
        $lead->stageChangeLogEntry(
            $stage,
            $stage->getId().': '.$stage->getName(),
            $this->translator->trans('mautic.stage.event.removed.batch')
        );

        return $this;
    }

    /**
     * @param string $channel
     *
     * @return array<mixed>
     */
    public function getFrequencyRules(Lead $lead, $channel = null)
    {
        if (is_array($channel)) {
            $channel = key($channel);
        }

        /** @var \Mautic\LeadBundle\Entity\FrequencyRuleRepository $frequencyRuleRepo */
        $frequencyRuleRepo = $this->em->getRepository(FrequencyRule::class);
        $frequencyRules    = $frequencyRuleRepo->getFrequencyRules($channel, $lead->getId());

        if (empty($frequencyRules)) {
            return [];
        }

        return $frequencyRules;
    }

    /**
     * Set frequency rules for lead per channel.
     *
     * @param array<mixed>    $data
     * @param array<LeadList> $leadLists
     *
     * @return bool Returns true
     */
    public function setFrequencyRules(Lead $lead, $data, $leadLists, $persist = true): bool
    {
        // One query to get all the lead's current frequency rules and go ahead and create entities for them
        $frequencyRules = $lead->getFrequencyRules()->toArray();
        $entities       = [];
        $channels       = $this->getPreferenceChannels();

        foreach ($channels as $ch) {
            if (empty($data['lead_channels']['preferred_channel'])) {
                $data['lead_channels']['preferred_channel'] = $ch;
            }

            $frequencyRule = $frequencyRules[$ch] ?? new FrequencyRule();
            $frequencyRule->setChannel($ch);
            $frequencyRule->setLead($lead);
            $frequencyRule->setDateAdded(new \DateTime());

            if (!empty($data['lead_channels']['frequency_number_'.$ch]) && !empty($data['lead_channels']['frequency_time_'.$ch])) {
                $frequencyRule->setFrequencyNumber($data['lead_channels']['frequency_number_'.$ch]);
                $frequencyRule->setFrequencyTime($data['lead_channels']['frequency_time_'.$ch]);
            } else {
                $frequencyRule->setFrequencyNumber(null);
                $frequencyRule->setFrequencyTime(null);
            }

            $frequencyRule->setPauseFromDate(!empty($data['lead_channels']['contact_pause_start_date_'.$ch]) ? $data['lead_channels']['contact_pause_start_date_'.$ch] : null);
            $frequencyRule->setPauseToDate(!empty($data['lead_channels']['contact_pause_end_date_'.$ch]) ? $data['lead_channels']['contact_pause_end_date_'.$ch] : null);

            $frequencyRule->setLead($lead);
            $frequencyRule->setPreferredChannel($data['lead_channels']['preferred_channel'] === $ch);

            if ($persist) {
                $entities[$ch] = $frequencyRule;
            } else {
                $lead->addFrequencyRule($frequencyRule);
            }
        }

        if (!empty($entities)) {
            $this->em->getRepository(FrequencyRule::class)->saveEntities($entities);
        }

        foreach ($data['lead_lists'] as $leadList) {
            if (!isset($leadLists[$leadList])) {
                $this->addToLists($lead, [$leadList]);
            }
        }
        // Delete lists that were removed
        $deletedLists = array_diff(array_keys($leadLists), $data['lead_lists']);
        if (!empty($deletedLists)) {
            $this->removeFromLists($lead, $deletedLists);
        }

        if (!empty($data['global_categories'])) {
            $this->addToCategory($lead, $data['global_categories']);
        }
        $leadCategories = $this->getLeadCategories($lead);

        // Update categories relations as removed those are removed.
        $unsubscribedCategories = array_diff($leadCategories, $data['global_categories']);

        if (!empty($unsubscribedCategories)) {
            $this->unsubscribeCategories($unsubscribedCategories);
        }

        // Delete channels that were removed
        $deleted = array_diff_key($frequencyRules, $entities);
        if (!empty($deleted)) {
            $this->em->getRepository(FrequencyRule::class)->deleteEntities($deleted);
        }

        return true;
    }

    /**
     * @param bool $manuallyAdded
     */
    public function addToCategory(Lead $lead, $categories, $manuallyAdded = true): array
    {
        $leadCategories = $this->getLeadCategoryRepository()->getLeadCategories($lead);

        $results = [];
        foreach ($categories as $category) {
            if (!isset($leadCategories[$category])) {
                $newLeadCategory = new LeadCategory();
                $newLeadCategory->setLead($lead);
                if (!$category instanceof Category) {
                    $category = $this->categoryModel->getEntity($category);
                }
                $newLeadCategory->setCategory($category);
                $newLeadCategory->setDateAdded(new \DateTime());
                $newLeadCategory->setManuallyAdded($manuallyAdded);
                $results[$category->getId()] = $newLeadCategory;

                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                    $this->dispatcher->dispatch(new CategoryChangeEvent($lead, $category), LeadEvents::LEAD_CATEGORY_CHANGE);
                }
            }
        }
        if (!empty($results)) {
            $this->getLeadCategoryRepository()->saveEntities($results);
        }

        return $results;
    }

    /**
     * @param mixed[] $categories
     */
    private function unsubscribeCategories(array $categories): void
    {
        $unsubscribedCats = [];
        foreach ($categories as $key => $category) {
            /** @var LeadCategory $category */
            $category     = $this->getLeadCategoryRepository()->getEntity($key);
            $category->setManuallyRemoved(true);
            $category->setManuallyAdded(false);

            $unsubscribedCats[] = $category;

            if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                $this->dispatcher->dispatch(new CategoryChangeEvent($category->getLead(), $category->getCategory(), false), LeadEvents::LEAD_CATEGORY_CHANGE);
            }
        }

        if (!empty($unsubscribedCats)) {
            $this->getLeadCategoryRepository()->saveEntities($unsubscribedCats);
        }
    }

    public function removeFromCategories($categories): void
    {
        $deleteCats = [];
        if (is_array($categories)) {
            foreach ($categories as $key => $category) {
                /** @var LeadCategory $category */
                $category     = $this->getLeadCategoryRepository()->getEntity($key);
                $deleteCats[] = $category;

                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                    $this->dispatcher->dispatch(new CategoryChangeEvent($category->getLead(), $category->getCategory(), false), LeadEvents::LEAD_CATEGORY_CHANGE);
                }
            }
        } elseif ($categories instanceof LeadCategory) {
            $deleteCats[] = $categories;

            if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                $this->dispatcher->dispatch(new CategoryChangeEvent($categories->getLead(), $categories->getCategory(), false), LeadEvents::LEAD_CATEGORY_CHANGE);
            }
        }

        if (!empty($deleteCats)) {
            $this->getLeadCategoryRepository()->deleteEntities($deleteCats);
        }
    }

    public function getLeadCategories(Lead $lead): array
    {
        $leadCategories   = $this->getLeadCategoryRepository()->getLeadCategories($lead);
        $leadCategoryList = [];
        foreach ($leadCategories as $category) {
            $leadCategoryList[$category['id']] = $category['category_id'];
        }

        return $leadCategoryList;
    }

    /**
     * @return mixed[]
     */
    public function getUnsubscribedLeadCategoriesIds(Lead $lead): array
    {
        $leadCategories   = $this->getLeadCategoryRepository()->getUnsubscribedLeadCategories($lead);
        $leadCategoryList = [];
        foreach ($leadCategories as $category) {
            $leadCategoryList[$category['id']] = $category['category_id'];
        }

        return $leadCategoryList;
    }

    /**
     * @param array $fields
     * @param array $data
     * @param bool  $persist
     * @param bool  $skipIfExists
     *
     * @throws \Exception
     */
    public function import($fields, $data, $owner = null, $list = null, $tags = null, $persist = true, ?LeadEventLog $eventLog = null, $importId = null, $skipIfExists = false): bool
    {
        $fields    = array_flip($fields);
        $fieldData = [];

        // Extract company data and import separately
        // Modifies the data array
        $company                           = null;
        [$companyFields, $companyData]     = $this->companyModel->extractCompanyDataFromImport($fields, $data);

        if (!empty($companyData)) {
            $company       = $this->companyModel->importCompany(array_flip($companyFields), $companyData);
        }

        foreach ($fields as $leadField => $importField) {
            // Prevent overwriting existing data with empty data
            if (array_key_exists($importField, $data) && !is_null($data[$importField]) && '' != $data[$importField]) {
                $fieldData[$leadField] = InputHelper::_($data[$importField], 'string');
            }
        }

        if (array_key_exists('id', $fieldData)) {
            $lead = $this->getEntity($fieldData['id']);
        }

        $lead ??= $this->checkForDuplicateContact($fieldData);
        $merged = (bool) $lead->getId();

        if (!empty($fields['dateAdded']) && !empty($data[$fields['dateAdded']])) {
            $dateAdded = new DateTimeHelper($data[$fields['dateAdded']]);
            $lead->setDateAdded($dateAdded->getUtcDateTime());
        }
        unset($fieldData['dateAdded']);

        if (!empty($fields['dateModified']) && !empty($data[$fields['dateModified']])) {
            $dateModified = new DateTimeHelper($data[$fields['dateModified']]);
            $lead->setDateModified($dateModified->getUtcDateTime());
        }
        unset($fieldData['dateModified']);

        if (!empty($fields['lastActive']) && !empty($data[$fields['lastActive']])) {
            $lastActive = new DateTimeHelper($data[$fields['lastActive']]);
            $lead->setLastActive($lastActive->getUtcDateTime());
        }
        unset($fieldData['lastActive']);

        if (!empty($fields['dateIdentified']) && !empty($data[$fields['dateIdentified']])) {
            $dateIdentified = new DateTimeHelper($data[$fields['dateIdentified']]);
            $lead->setDateIdentified($dateIdentified->getUtcDateTime());
        }
        unset($fieldData['dateIdentified']);

        if (!empty($fields['createdByUser']) && !empty($data[$fields['createdByUser']])) {
            $userRepo      = $this->em->getRepository(User::class);
            $createdByUser = $userRepo->findByIdentifier($data[$fields['createdByUser']]);
            if (null !== $createdByUser) {
                $lead->setCreatedBy($createdByUser);
            }
        }
        unset($fieldData['createdByUser']);

        if (!empty($fields['modifiedByUser']) && !empty($data[$fields['modifiedByUser']])) {
            $userRepo       = $this->em->getRepository(User::class);
            $modifiedByUser = $userRepo->findByIdentifier($data[$fields['modifiedByUser']]);
            if (null !== $modifiedByUser) {
                $lead->setModifiedBy($modifiedByUser);
            }
        }
        unset($fieldData['modifiedByUser']);

        if (!empty($fields['ip']) && !empty($data[$fields['ip']])) {
            $addresses = explode(',', $data[$fields['ip']]);
            foreach ($addresses as $address) {
                $address = trim($address);
                if (!$ipAddress = $this->ipAddressModel->findOneByIpAddress($address)) {
                    $ipAddress = new IpAddress();
                    $ipAddress->setIpAddress($address);
                }
                $lead->addIpAddress($ipAddress);
            }
        }
        unset($fieldData['ip']);

        if (!empty($fields['points']) && !empty($data[$fields['points']]) && null === $lead->getId()) {
            // Add points only for new leads
            $lead->setPoints($data[$fields['points']]);

            // add a lead point change log
            $log = new PointsChangeLog();
            $log->setDelta($data[$fields['points']]);
            $log->setLead($lead);
            $log->setType('lead');
            $log->setEventName($this->translator->trans('mautic.lead.import.event.name'));
            $log->setActionName($this->translator->trans('mautic.lead.import.action.name', [
                '%name%' => $this->userHelper->getUser()->getUsername(),
            ]));
            $log->setIpAddress($this->ipLookupHelper->getIpAddress());
            $log->setDateAdded(new \DateTime());
            $lead->addPointsChangeLog($log);
        }

        if (!empty($fields['stage']) && !empty($data[$fields['stage']])) {
            static $stages = [];
            $stageName     = $data[$fields['stage']];
            if (!array_key_exists($stageName, $stages)) {
                // Set stage for contact
                $stage = $this->em->getRepository(Stage::class)->getStageByName($stageName);

                if (empty($stage)) {
                    $stage = new Stage();
                    $stage->setName($stageName);
                    $stages[$stageName] = $stage;
                }
            } else {
                $stage = $stages[$stageName];
            }

            $lead->setStage($stage);

            // add a contact stage change log
            $log = new StagesChangeLog();
            $log->setStage($stage);
            $log->setEventName($stage->getId().':'.$stage->getName());
            $log->setLead($lead);
            $log->setActionName(
                $this->translator->trans(
                    'mautic.stage.import.action.name',
                    [
                        '%name%' => $this->userHelper->getUser()->getUsername(),
                    ]
                )
            );
            $log->setDateAdded(new \DateTime());
            $lead->stageChangeLog($log);
        }
        unset($fieldData['stage']);

        // Set unsubscribe status
        if (!empty($fields['doNotEmail']) && isset($data[$fields['doNotEmail']]) && (!empty($fields['email']) && !empty($data[$fields['email']]))) {
            $doNotEmail = filter_var($data[$fields['doNotEmail']], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (null !== $doNotEmail) {
                $reason = $this->translator->trans('mautic.lead.import.by.user', [
                    '%user%' => $this->userHelper->getUser()->getUsername(),
                ]);

                // The email must be set for successful unsubscribtion
                $lead->addUpdatedField('email', $data[$fields['email']]);
                if ($doNotEmail) {
                    $event = new DoNotContactAddEvent($lead, 'email', $reason, DNC::MANUAL);
                    $this->dispatcher->dispatch($event, DoNotContactAddEvent::ADD_DONOT_CONTACT);
                } else {
                    $event = new DoNotContactRemoveEvent($lead, 'email');
                    $this->dispatcher->dispatch($event, DoNotContactRemoveEvent::REMOVE_DONOT_CONTACT);
                }
            }
        }

        unset($fieldData['doNotEmail']);

        if (!empty($fields['ownerusername']) && !empty($data[$fields['ownerusername']])) {
            try {
                $newOwner = $this->userProvider->loadUserByIdentifier($data[$fields['ownerusername']]);
                $lead->setOwner($newOwner);
                // reset default import owner if exists owner for contact
                $owner = null;
            } catch (NonUniqueResultException) {
                // user not found
            }
        }
        unset($fieldData['ownerusername']);

        if (!empty($fields['tags']) && !empty($data[$fields['tags']])) {
            $leadTags = explode('|', $data[$fields['tags']]);
            $this->modifyTags($lead, $leadTags, null, false);
        }
        unset($fieldData['tags']);

        if (null !== $owner) {
            $lead->setOwner($this->em->getReference(User::class, $owner));
        }

        if (null !== $tags) {
            $this->modifyTags($lead, $tags, null, false);
        }

        if (empty($this->leadFields)) {
            $this->leadFields = $this->leadFieldModel->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'f.isPublished',
                                'expr'   => 'eq',
                                'value'  => true,
                            ],
                            [
                                'column' => 'f.object',
                                'expr'   => 'eq',
                                'value'  => 'lead',
                            ],
                        ],
                    ],
                    'hydration_mode' => 'HYDRATE_ARRAY',
                    'result_cache'   => new ResultCacheOptions(LeadField::CACHE_NAMESPACE),
                ]
            );
        }

        $fieldErrors = [];

        foreach ($this->leadFields as $leadField) {
            // Skip If value already exists
            if ($skipIfExists && !$lead->isNew() && !empty($lead->getFieldValue($leadField['alias']))) {
                unset($fieldData[$leadField['alias']]);
                continue;
            }

            if (isset($fieldData[$leadField['alias']])) {
                if ('NULL' === $fieldData[$leadField['alias']]) {
                    $fieldData[$leadField['alias']] = null;

                    continue;
                }

                try {
                    $this->cleanFields($fieldData, $leadField);
                } catch (\Exception $exception) {
                    $fieldErrors[] = $leadField['alias'].': '.$exception->getMessage();
                }

                if ('email' === $leadField['type'] && !empty($fieldData[$leadField['alias']])) {
                    try {
                        $this->emailValidator->validate($fieldData[$leadField['alias']], false);
                    } catch (\Exception $exception) {
                        $fieldErrors[] = $leadField['alias'].': '.$exception->getMessage();
                    }
                }

                // Skip if the value is in the CSV row
                continue;
            } elseif ($lead->isNew() && $leadField['defaultValue']) {
                // Fill in the default value if any
                $fieldData[$leadField['alias']] = ('multiselect' === $leadField['type']) ? [$leadField['defaultValue']] : $leadField['defaultValue'];
            }
        }

        if ($fieldErrors) {
            $fieldErrors = implode("\n", $fieldErrors);

            throw new \Exception($fieldErrors);
        }

        $this->setFieldValues($lead, $fieldData, false, false, true);

        $lead->imported = true;

        if ($eventLog) {
            $action = $merged ? 'updated' : 'inserted';
            $eventLog->setAction($action);
        }

        if ($persist) {
            $lead->setManipulator(new LeadManipulator(
                'lead',
                'import',
                $importId,
                $this->userHelper->getUser()->getName()
            ));
            $this->saveEntity($lead);

            if (null !== $list) {
                $this->addToLists($lead, [$list]);
            }

            if (null !== $company) {
                $this->companyModel->addLeadToCompany($company, $lead);
                $this->setPrimaryCompany($company->getId(), $lead->getId());
            }

            if ($eventLog) {
                $lead->addEventLog($eventLog);
            }
        }

        return $merged;
    }

    /**
     * Update a leads tags.
     *
     * @param bool|false $removeOrphans
     */
    public function setTags(Lead $lead, array $tags, $removeOrphans = false): void
    {
        /** @var Tag[] $currentTags */
        $currentTags  = $lead->getTags();
        $leadModified = $tagsDeleted = false;

        foreach ($currentTags as $tag) {
            if (!in_array($tag->getId(), $tags)) {
                // Tag has been removed
                $lead->removeTag($tag);
                $leadModified = $tagsDeleted = true;
            } else {
                // Remove tag so that what's left are new tags
                $key = array_search($tag->getId(), $tags);
                unset($tags[$key]);
            }
        }

        if (!empty($tags)) {
            foreach ($tags as $tag) {
                if (is_numeric($tag)) {
                    // Existing tag being added to this lead
                    $lead->addTag(
                        $this->em->getReference(Tag::class, $tag)
                    );
                } else {
                    $lead->addTag(
                        $this->getTagRepository()->getTagByNameOrCreateNewOne($tag)
                    );
                }
            }
            $leadModified = true;
        }

        if ($leadModified) {
            $this->saveEntity($lead);

            // Delete orphaned tags
            if ($tagsDeleted && $removeOrphans) {
                $this->getTagRepository()->deleteOrphans();
            }
        }
    }

    /**
     * Update a leads UTM tags.
     */
    public function setUtmTags(Lead $lead, UtmTag $utmTags): void
    {
        $lead->setUtmTags($utmTags);

        $this->saveEntity($lead);
    }

    /**
     * Add leads UTM tags via API.
     *
     * @param array $params
     */
    public function addUTMTags(Lead $lead, $params): void
    {
        // known "synonym" fields expected
        $synonyms = ['useragent'  => 'user_agent',
            'remotehost'          => 'remote_host', ];

        // convert 'query' option to an array if necessary
        if (isset($params['query']) && !is_array($params['query'])) {
            // assume it's a query string; convert it to array
            parse_str($params['query'], $queryResult);
            if (!empty($queryResult)) {
                $params['query'] = $queryResult;
            } else {
                // Something wrong with, remove it
                unset($params['query']);
            }
        }

        // Fix up known synonym/mismatch field names
        foreach ($synonyms as $expected => $replace) {
            if (array_key_exists($expected, $params) && !isset($params[$replace])) {
                // add expected key name
                $params[$replace] = $params[$expected];
            }
        }

        // see if active date set, so we can use it
        $updateLastActive = false;
        $lastActive       = new \DateTime();
        // should be: yyyy-mm-ddT00:00:00+00:00
        if (isset($params['lastActive'])) {
            $lastActive       = new \DateTime($params['lastActive']);
            $updateLastActive = true;
        }
        $params['date_added'] = $lastActive;

        // New utmTag
        $utmTags = new UtmTag();

        // get available fields and their setter.
        $fields = $utmTags->getFieldSetterList();

        // cycle through calling appropriate setter
        foreach ($fields as $q => $setter) {
            if (isset($params[$q])) {
                $utmTags->$setter($params[$q]);
            }
        }

        // create device
        if (!empty($params['useragent'])) {
            $this->deviceTracker->createDeviceFromUserAgent($lead, $params['useragent']);
        }

        // add the lead
        $utmTags->setLead($lead);
        if ($updateLastActive) {
            $lead->setLastActive($lastActive);
        }

        $this->setUtmTags($lead, $utmTags);
    }

    /**
     * Removes a UtmTag set from a Lead.
     *
     * @param int $utmId
     */
    public function removeUtmTags(Lead $lead, $utmId): bool
    {
        /** @var UtmTag $utmTag */
        foreach ($lead->getUtmTags() as $utmTag) {
            if ($utmTag->getId() === $utmId) {
                $lead->removeUtmTagEntry($utmTag);
                $this->saveEntity($lead);

                return true;
            }
        }

        return false;
    }

    /**
     * Modify tags with support to remove via a prefixed minus sign.
     *
     * @param bool $persist True if tags modified
     */
    public function modifyTags(Lead $lead, $tags, ?array $removeTags = null, $persist = true): bool
    {
        $tagsModified = false;
        $leadTags     = $lead->getTags();

        if (!$leadTags->isEmpty()) {
            $this->logger->debug('CONTACT: Contact currently has tags '.implode(', ', $leadTags->getKeys()));
        } else {
            $this->logger->debug('CONTACT: Contact currently does not have any tags');
        }

        if (!is_array($tags)) {
            $tags = explode(',', $tags);
        }

        if (empty($tags) && empty($removeTags)) {
            return false;
        }

        $this->logger->debug('CONTACT: Adding '.implode(', ', $tags).' to contact ID# '.$lead->getId());

        array_walk($tags, function (&$val): void {
            $val = html_entity_decode(trim($val), ENT_QUOTES);
            $val = InputHelper::_($val, 'string');
        });
        // Remove any tags that became empty after filtering
        $tags = array_filter($tags, 'strlen');

        // See which tags already exist
        $foundTags = $this->getTagRepository()->getTagsByName($tags);
        foreach ($tags as $tag) {
            if (str_starts_with($tag, '-')) {
                // Tag to be removed
                $tag = substr($tag, 1);

                if (array_key_exists($tag, $foundTags) && $leadTags->contains($foundTags[$tag])) {
                    $tagsModified = true;
                    $lead->removeTag($foundTags[$tag]);

                    $this->logger->debug('CONTACT: Removed '.$tag);
                }
            } else {
                $tagToBeAdded = null;

                if (!array_key_exists($tag, $foundTags)) {
                    $tagToBeAdded = new Tag($tag, false);
                } elseif (!$leadTags->contains($foundTags[$tag])) {
                    $tagToBeAdded = $foundTags[$tag];
                }

                if ($tagToBeAdded) {
                    $lead->addTag($tagToBeAdded);
                    $tagsModified = true;
                    $this->logger->debug('CONTACT: Added '.$tag);
                }
            }
        }

        if (!empty($removeTags)) {
            $this->logger->debug('CONTACT: Removing '.implode(', ', $removeTags).' for contact ID# '.$lead->getId());

            array_walk($removeTags, function (&$val): void {
                $val = html_entity_decode(trim($val), ENT_QUOTES);
                $val = InputHelper::_($val, 'string');
            });
            // Remove any tags that became empty after filtering
            $removeTags = array_filter($removeTags, 'strlen');

            // See which tags really exist
            $foundRemoveTags = $this->getTagRepository()->getTagsByName($removeTags);

            foreach ($removeTags as $tag) {
                // Tag to be removed
                if (array_key_exists($tag, $foundRemoveTags) && $leadTags->contains($foundRemoveTags[$tag])) {
                    $lead->removeTag($foundRemoveTags[$tag]);
                    $tagsModified = true;

                    $this->logger->debug('CONTACT: Removed '.$tag);
                }
            }
        }

        if ($persist) {
            $this->saveEntity($lead);
        }

        return $tagsModified;
    }

    /**
     * Modify companies for lead.
     *
     * @param int[] $companies
     */
    public function modifyCompanies(Lead $lead, array $companies): void
    {
        // See which companies belong to the lead already
        $leadCompanies = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId());

        $requestedCompanies = new Collection($companies);
        $currentCompanies   = (new Collection($leadCompanies))->keyBy('company_id');

        // Add companies that are not in the array of found companies
        $addCompanies = $requestedCompanies->reject(
            // Reject if the lead is already in the given company
            fn ($companyId) => $currentCompanies->has($companyId)
        );
        if ($addCompanies->count()) {
            $this->companyModel->addLeadToCompany($addCompanies->toArray(), $lead);
        }

        // Remove companies that are not in the array of given companies
        $removeCompanies = $currentCompanies->reject(
            fn (array $company) =>
                // Reject if the found company is still in the list of companies given
                $requestedCompanies->contains($company['company_id'])
        );
        if ($removeCompanies->count()) {
            $this->companyModel->removeLeadFromCompany($removeCompanies->keys()->toArray(), $lead);
        }
    }

    /**
     * Get array of available lead tags.
     *
     * @return mixed[]
     */
    public function getTagList(): array
    {
        return $this->getTagRepository()->getSimpleList(null, [], 'tag', 'id');
    }

    /**
     * Get bar chart data of contacts.
     *
     * @param string    $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param bool      $canViewOthers
     */
    public function getLeadsLineChartData($unit, $dateFrom, $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true): array
    {
        $flag        = null;
        $topLists    = null;
        $allLeadsT   = $this->translator->trans('mautic.lead.all.leads');
        $identifiedT = $this->translator->trans('mautic.lead.identified');
        $anonymousT  = $this->translator->trans('mautic.lead.lead.anonymous');

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $chart                              = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query                              = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $anonymousFilter                    = $filter;
        $anonymousFilter['date_identified'] = [
            'expression' => 'isNull',
        ];
        $identifiedFilter                    = $filter;
        $identifiedFilter['date_identified'] = [
            'expression' => 'isNotNull',
        ];

        if ('top' == $flag) {
            $topLists = $this->leadListModel->getTopLists(6, $dateFrom, $dateTo);
            foreach ($topLists as $list) {
                $filter['leadlist_id'] = [
                    'value'            => $list['id'],
                    'list_column_name' => 't.id',
                ];
                $all = $query->fetchTimeData('leads', 'date_added', $filter);
                $chart->setDataset($list['name'].': '.$allLeadsT, $all);
            }
        } elseif ('topIdentifiedVsAnonymous' == $flag) {
            $topLists = $this->leadListModel->getTopLists(3, $dateFrom, $dateTo);
            foreach ($topLists as $list) {
                $anonymousFilter['leadlist_id'] = [
                    'value'            => $list['id'],
                    'list_column_name' => 't.id',
                ];
                $identifiedFilter['leadlist_id'] = [
                    'value'            => $list['id'],
                    'list_column_name' => 't.id',
                ];
                $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
                $anonymous  = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
                $chart->setDataset($list['name'].': '.$identifiedT, $identified);
                $chart->setDataset($list['name'].': '.$anonymousT, $anonymous);
            }
        } elseif ('identified' == $flag) {
            $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
            $chart->setDataset($identifiedT, $identified);
        } elseif ('anonymous' == $flag) {
            $anonymous = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
            $chart->setDataset($anonymousT, $anonymous);
        } elseif ('identifiedVsAnonymous' == $flag) {
            $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
            $anonymous  = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
            $chart->setDataset($identifiedT, $identified);
            $chart->setDataset($anonymousT, $anonymous);
        } else {
            $all = $query->fetchTimeData('leads', 'date_added', $filter);
            $chart->setDataset($allLeadsT, $all);
        }

        return $chart->render();
    }

    /**
     * Get pie chart data of dwell times.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     */
    public function getAnonymousVsIdentifiedPieChartData($dateFrom, $dateTo, $filters = [], $canViewOthers = true): array
    {
        $chart = new PieChart();
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $identified = $query->count('leads', 'date_identified', 'date_added', $filters);
        $all        = $query->count('leads', 'id', 'date_added', $filters);
        $chart->setDataset($this->translator->trans('mautic.lead.identified'), $identified);
        $chart->setDataset($this->translator->trans('mautic.lead.lead.anonymous'), $all - $identified);

        return $chart->render();
    }

    /**
     * Get leads count per country name.
     * Can't use entity, because country is a custom field.
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param mixed[]   $filters
     * @param bool      $canViewOthers
     */
    public function getLeadMapData($dateFrom, $dateTo, $filters = [], $canViewOthers = true): array
    {
        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) as quantity, t.country')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->groupBy('t.country')
            ->where($q->expr()->isNotNull('t.country'));

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results   = $q->executeQuery()->fetchAllAssociative();
        $countries = array_flip(Countries::getNames('en'));
        $mapData   = [];

        // Convert country names to 2-char code
        if ($results) {
            foreach ($results as $leadCountry) {
                if (isset($countries[$leadCountry['country']])) {
                    $mapData[$countries[$leadCountry['country']]] = $leadCountry['quantity'];
                }
            }
        }

        return $mapData;
    }

    /**
     * @param string[] $aliases
     *
     * @return mixed[]
     *
     * @throws DBALException
     */
    public function getCustomLeadFieldLength(array $aliases): array
    {
        $columns = [];
        foreach ($aliases as $alias) {
            $columns[] = sprintf('max(CHAR_LENGTH(%s)) %s', $alias, $alias);
        }

        $query = $this->em->getConnection()->createQueryBuilder();
        $query->select(implode(', ', $columns))
            ->from(MAUTIC_TABLE_PREFIX.'leads');

        return $query->executeQuery()->fetchAssociative();
    }

    /**
     * Get a list of top (by leads owned) users.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     *
     * @return array
     */
    public function getTopOwners($limit = 10, $dateFrom = null, $dateTo = null, $filters = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) AS leads, t.owner_id, u.first_name, u.last_name')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = t.owner_id')
            ->where($q->expr()->isNotNull('t.owner_id'))
            ->orderBy('leads', 'DESC')
            ->groupBy('t.owner_id, u.first_name, u.last_name')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get a list of top (by leads owned) users.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     *
     * @return array
     */
    public function getTopCreators($limit = 10, $dateFrom = null, $dateTo = null, $filters = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) AS leads, t.created_by, t.created_by_user')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->where($q->expr()->isNotNull('t.created_by'))
            ->andWhere($q->expr()->isNotNull('t.created_by_user'))
            ->orderBy('leads', 'DESC')
            ->groupBy('t.created_by, t.created_by_user')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get a list of leads in a date range.
     *
     * @param int   $limit
     * @param array $filters
     * @param array $options
     *
     * @return array
     */
    public function getLeadList($limit = 10, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null, $filters = [], $options = [])
    {
        if (!empty($options['canViewOthers'])) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.firstname, t.lastname, t.email, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        if (empty($options['includeAnonymous'])) {
            $q->andWhere($q->expr()->isNotNull('t.date_identified'));
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        if ($results) {
            foreach ($results as &$result) {
                if ($result['firstname'] || $result['lastname']) {
                    $result['name'] = trim($result['firstname'].' '.$result['lastname']);
                } elseif ($result['email']) {
                    $result['name'] = $result['email'];
                } else {
                    $result['name'] = 'anonymous';
                }
                unset($result['firstname']);
                unset($result['lastname']);
                unset($result['email']);
            }
        }

        return $results;
    }

    /**
     * @param array<mixed, mixed>|null $filters
     */
    public function getEngagements(?Lead $lead = null, ?array $filters = null, ?array $orderBy = null, int $page = 1, int $limit = 25, bool $forTimeline = true): array
    {
        $event = $this->dispatcher->dispatch(
            new LeadTimelineEvent($lead, $filters, $orderBy, $page, $limit, $forTimeline, $this->coreParametersHelper->get('site_url')),
            LeadEvents::TIMELINE_ON_GENERATE
        );

        $payload = [
            'events'   => $event->getEvents(),
            'filters'  => $filters,
            'order'    => $orderBy,
            'types'    => $event->getEventTypes(),
            'total'    => $event->getEventCounter()['total'],
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => $event->getMaxPage(),
        ];

        return ($forTimeline) ? $payload : [$payload, $event->getSerializerGroups()];
    }

    /**
     * @return array
     */
    public function getEngagementTypes()
    {
        $event = new LeadTimelineEvent();
        $event->fetchTypesOnly();

        $this->dispatcher->dispatch($event, LeadEvents::TIMELINE_ON_GENERATE);

        return $event->getEventTypes();
    }

    /**
     * Get engagement counts by time unit.
     *
     * @param string $unit
     */
    public function getEngagementCount(Lead $lead, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null, $unit = 'm', ?ChartQuery $chartQuery = null): array
    {
        $event = new LeadTimelineEvent($lead);
        $event->setCountOnly($dateFrom, $dateTo, $unit, $chartQuery);

        $this->dispatcher->dispatch($event, LeadEvents::TIMELINE_ON_GENERATE);

        return $event->getEventCounter();
    }

    public function addToCompany(Lead $lead, $company): bool
    {
        // check if lead is in company already
        if (!$company instanceof Company) {
            $company = $this->companyModel->getEntity($company);
        }

        // company does not exist anymore
        if (null === $company) {
            return false;
        }

        $companyLead = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId(), $company->getId());

        if (empty($companyLead)) {
            $this->companyModel->addLeadToCompany($company, $lead);

            return true;
        }

        return false;
    }

    /**
     * Get contact channels.
     */
    public function getContactChannels(Lead $lead): array
    {
        $allChannels = $this->getPreferenceChannels();

        $channels = [];
        foreach ($allChannels as $channel) {
            if (DNC::IS_CONTACTABLE === $this->isContactable($lead, $channel)) {
                $channels[$channel] = $channel;
            }
        }

        return $channels;
    }

    /**
     * Get contact channels.
     */
    public function getDoNotContactChannels(Lead $lead): array
    {
        $allChannels = $this->getPreferenceChannels();

        $channels = [];
        foreach ($allChannels as $channel) {
            if (DNC::IS_CONTACTABLE !== $this->isContactable($lead, $channel)) {
                $channels[$channel] = $channel;
            }
        }

        return $channels;
    }

    public function getPreferenceChannels(): array
    {
        return $this->channelListHelper->getFeatureChannels(self::CHANNEL_FEATURE, true);
    }

    /**
     * @return array
     */
    public function getPreferredChannel(Lead $lead)
    {
        $preferredChannel = $this->getFrequencyRuleRepository()->getPreferredChannel($lead->getId());
        if (!empty($preferredChannel)) {
            return $preferredChannel[0];
        }

        return [];
    }

    /**
     * @return mixed[]
     */
    public function setPrimaryCompany($companyId, $leadId)
    {
        $companyArray      = [];
        $oldPrimaryCompany = $newPrimaryCompany = false;

        $lead = $this->getEntity($leadId);

        $companyLeads = $this->companyModel->getCompanyLeadRepository()->getEntitiesByLead($lead);

        /** @var CompanyLead $companyLead */
        foreach ($companyLeads as $companyLead) {
            $company = $companyLead->getCompany();

            if ($companyLead) {
                if ($companyLead->getPrimary() && !$oldPrimaryCompany) {
                    $oldPrimaryCompany = $companyLead->getCompany()->getId();
                }
                if ($company->getId() === (int) $companyId) {
                    $companyLead->setPrimary(true);
                    $newPrimaryCompany = $companyId;
                    $lead->addUpdatedField('company', $company->getName());
                } else {
                    $companyLead->setPrimary(false);
                }
                $companyArray[] = $companyLead;
            }
        }

        if (!$newPrimaryCompany) {
            $latestCompany = $this->companyModel->getCompanyLeadRepository()->getLatestCompanyForLead($leadId);
            if (!empty($latestCompany)) {
                $lead->addUpdatedField('company', $latestCompany['companyname'])
                    ->setDateModified(new \DateTime());
            }
        }

        if (!empty($companyArray)) {
            $this->em->getRepository(Lead::class)->saveEntity($lead);
            $this->companyModel->getCompanyLeadRepository()->saveEntities($companyArray, false);
        }

        // Clear CompanyLead entities from Doctrine memory
        $this->companyModel->getCompanyLeadRepository()->detachEntities($companyLeads);

        return ['oldPrimary' => $oldPrimaryCompany, 'newPrimary' => $companyId];
    }

    public function scoreContactsCompany(Lead $lead, $score): bool
    {
        $success          = false;
        $entities         = [];
        $contactCompanies = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId());

        foreach ($contactCompanies as $contactCompany) {
            $company  = $this->companyModel->getEntity($contactCompany['company_id']);
            $oldScore = $company->getScore();
            $newScore = $score + $oldScore;
            $company->setScore($newScore);
            $entities[] = $company;
            $success    = true;
        }

        if (!empty($entities)) {
            $this->companyModel->getRepository()->saveEntities($entities);
        }

        return $success;
    }

    public function updateLeadOwner(Lead $lead, $ownerId): void
    {
        $owner = $this->em->getReference(User::class, $ownerId);
        $lead->setOwner($owner);

        parent::saveEntity($lead);
    }

    private function processManipulator(Lead $lead): void
    {
        if ($lead->isNewlyCreated() || $lead->wasAnonymous()) {
            // Only store an entry once for created and once for identified, not every time the lead is saved
            $manipulator = $lead->getManipulator();
            if (null !== $manipulator && !$manipulator->wasLogged()) {
                $manipulationLog = new LeadEventLog();
                $manipulationLog->setLead($lead)
                    ->setBundle($manipulator->getBundleName())
                    ->setObject($manipulator->getObjectName())
                    ->setObjectId($manipulator->getObjectId());
                if ($lead->isAnonymous()) {
                    $manipulationLog->setAction('created_contact');
                } else {
                    $manipulationLog->setAction('identified_contact');
                }
                $description = $manipulator->getObjectDescription();
                $manipulationLog->setProperties(['object_description' => $description]);

                $lead->addEventLog($manipulationLog);
                $manipulator->setAsLogged();
            }
        }
    }

    /**
     * @param bool $persist
     */
    protected function createNewContact(IpAddress $ip, $persist = true): Lead
    {
        // let's create a lead
        $lead = new Lead();
        $lead->addIpAddress($ip);
        $lead->setNewlyCreated(true);

        if ($persist && !defined('MAUTIC_NON_TRACKABLE_REQUEST')) {
            // Set to prevent loops
            $this->contactTracker->setTrackedContact($lead);

            // Note ignoring a lead manipulator object here on purpose to not falsely record entries
            $this->saveEntity($lead, false);

            $fields = $this->getLeadDetails($lead);
            $lead->setFields($fields);
        }

        if ($leadId = $lead->getId()) {
            $this->logger->debug("LEAD: New lead created with ID# $leadId.");
        }

        return $lead;
    }

    /**
     * @deprecated 2.12.0 to be removed in 3.0; use Mautic\LeadBundle\Model\DoNotContact instead
     *
     * @param string $channel
     *
     * @return int
     *
     * @see DNC This method can return boolean false, so be
     *                                             sure to always compare the return value against
     *                                             the class constants of DoNotContact
     */
    public function isContactable(Lead $lead, $channel)
    {
        if (is_array($channel)) {
            $channel = key($channel);
        }

        /** @var \Mautic\LeadBundle\Entity\DoNotContactRepository $dncRepo */
        $dncRepo = $this->em->getRepository(DNC::class);

        $dncEntries = $dncRepo->getEntriesByLeadAndChannel($lead, $channel);

        // If the lead has no entries in the DNC table, we're good to go
        if (empty($dncEntries)) {
            return DNC::IS_CONTACTABLE;
        }

        foreach ($dncEntries as $dnc) {
            if (DNC::IS_CONTACTABLE !== $dnc->getReason()) {
                return $dnc->getReason();
            }
        }

        return DNC::IS_CONTACTABLE;
    }

    public function getAvailableLeadFields(): array
    {
        return $this->availableLeadFields;
    }

    /**
     * @return array<string, int|float>
     */
    public function getLeadEmailStats(Lead $lead): array
    {
        /** @var StatRepository $statRepository */
        $statRepository = $this->em->getRepository(Stat::class);

        return $statRepository->getStatsSummaryForContacts([$lead->getId()])[$lead->getId()];
    }

    public function removeTagFromLead(int $leadId, int $tagId): void
    {
        $lead = $this->getEntity($leadId);
        $tag  = $this->getTagRepository()->find($tagId);

        if ($lead && $tag) {
            $lead->removeTag($tag);
            $this->saveEntity($lead);
        }
    }

    /**
     * @param array<mixed>|null $fields
     */
    private function validateSelectFields(Lead $entity, ?array $fields): void
    {
        if (is_null($fields)) {
            return;
        }
        foreach ($fields as $groupFields) {
            foreach ($groupFields as $field) {
                if (!is_array($field)) {
                    return;
                } elseif ('select' !== $field['type']) {
                    continue;
                }
                $allowedValues = is_array($field['properties'])
                    ? $field['properties']
                    : unserialize($field['properties']);

                $flattenedAllowedValues = array_map(fn ($item): string => html_entity_decode($item['value'], ENT_QUOTES), $allowedValues['list']);

                if (!empty($allowedValues['list']) && !in_array($field['value'], $flattenedAllowedValues)) {
                    // if the set value of the field is not present allowed values array,
                    // update the field value to null
                    $entity->addUpdatedField($field['alias'], null);
                }
            }
        }
    }
}
