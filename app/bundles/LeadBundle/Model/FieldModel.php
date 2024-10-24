<?php

namespace Mautic\LeadBundle\Model;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Paginator\SimplePaginator;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\CustomFieldColumn;
use Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Exception\CustomFieldLimitException;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Field\LeadFieldSaver;
use Mautic\LeadBundle\Field\SchemaDefinition;
use Mautic\LeadBundle\Form\Type\FieldType;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<LeadField>
 */
class FieldModel extends FormModel
{
    public static $coreFields = [
        // Listed according to $order for installation
        'title' => [
            'type'       => 'lookup',
            'properties' => [
                'list' => [
                    'Mr',
                    'Mrs',
                    'Miss',
                ],
            ],
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'firstname' => [
            'fixed'    => true,
            'short'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'lastname' => [
            'fixed'    => true,
            'short'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'company' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'position' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'email' => [
            'type'     => 'email',
            'unique'   => true,
            'fixed'    => true,
            'short'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'mobile' => [
            'type'     => 'tel',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'phone' => [
            'type'     => 'tel',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'points' => [
            'type'     => 'number',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
            'default'  => 0,
        ],
        'fax' => [
            'type'     => 'tel',
            'listable' => true,
            'object'   => 'lead',
        ],
        'address1' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'address2' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'city' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'state' => [
            'type'     => 'region',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'zipcode' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'country' => [
            'type'     => 'country',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'preferred_locale' => [
            'type'     => 'locale',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'timezone' => [
            'type'     => 'timezone',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'last_active' => [
            'type'     => 'datetime',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'attribution_date' => [
            'type'     => 'datetime',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'attribution' => [
            'type'       => 'number',
            'properties' => ['roundmode' => 4, 'scale' => 2],
            'fixed'      => true,
            'listable'   => true,
            'object'     => 'lead',
        ],
        'website' => [
            'type'     => 'url',
            'listable' => true,
            'object'   => 'lead',
        ],
        'facebook' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'foursquare' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'instagram' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'linkedin' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'skype' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'twitter' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
    ];

    public static $coreCompanyFields = [
        // Listed according to $order for installation
        'companyaddress1' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyaddress2' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyemail' => [
            'type'     => 'email',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyphone' => [
            'type'     => 'tel',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companycity' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companystate' => [
            'type'     => 'region',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyzipcode' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companycountry' => [
            'type'     => 'country',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyname' => [
            'fixed'    => true,
            'required' => true,
            'listable' => true,
            'unique'   => true,
            'object'   => 'company',
        ],
        'companywebsite' => [
            'fixed'    => true,
            'type'     => 'url',
            'listable' => true,
            'object'   => 'company',
        ],
        'companynumber_of_employees' => [
            'type'       => 'number',
            'properties' => ['roundmode' => 4, 'scale' => 0],
            'group'      => 'professional',
            'listable'   => true,
            'object'     => 'company',
        ],
        'companyfax' => [
            'type'     => 'tel',
            'listable' => true,
            'group'    => 'professional',
            'object'   => 'company',
        ],
        'companyannual_revenue' => [
            'type'       => 'number',
            'properties' => ['roundmode' => 4, 'scale' => 2],
            'listable'   => true,
            'group'      => 'professional',
            'object'     => 'company',
        ],
        'companyindustry' => [
            'type'       => 'select',
            'group'      => 'professional',
            'properties' => [
                'list' => [
                    [
                        'label' => 'Aerospace & Defense',
                        'value' => 'Aerospace & Defense',
                    ],
                    [
                        'label' => 'Agriculture',
                        'value' => 'Agriculture',
                    ],
                    [
                        'label' => 'Apparel',
                        'value' => 'Apparel',
                    ],
                    [
                        'label' => 'Automotive & Assembly',
                        'value' => 'Automotive & Assembly',
                    ],
                    [
                        'label' => 'Banking',
                        'value' => 'Banking',
                    ],
                    [
                        'label' => 'Biotechnology',
                        'value' => 'Biotechnology',
                    ],
                    [
                        'label' => 'Chemicals',
                        'value' => 'Chemicals',
                    ],
                    [
                        'label' => 'Communications',
                        'value' => 'Communications',
                    ],
                    [
                        'label' => 'Construction',
                        'value' => 'Construction',
                    ],
                    [
                        'label' => 'Consumer Packaged Goods',
                        'value' => 'Consumer Packaged Goods',
                    ],
                    [
                        'label' => 'Education',
                        'value' => 'Education',
                    ],
                    [
                        'label' => 'Electronics',
                        'value' => 'Electronics',
                    ],
                    [
                        'label' => 'Energy',
                        'value' => 'Energy',
                    ],
                    [
                        'label' => 'Engineering',
                        'value' => 'Engineering',
                    ],
                    [
                        'label' => 'Entertainment',
                        'value' => 'Entertainment',
                    ],
                    [
                        'label' => 'Environmental',
                        'value' => 'Environmental',
                    ],
                    [
                        'label' => 'Finance',
                        'value' => 'Finance',
                    ],
                    [
                        'label' => 'Food & Beverage',
                        'value' => 'Food & Beverage',
                    ],
                    [
                        'label' => 'Government',
                        'value' => 'Government',
                    ],
                    [
                        'label' => 'Healthcare',
                        'value' => 'Healthcare',
                    ],
                    [
                        'label' => 'Hospitality',
                        'value' => 'Hospitality',
                    ],
                    [
                        'label' => 'Insurance',
                        'value' => 'Insurance',
                    ],
                    [
                        'label' => 'Machinery',
                        'value' => 'Machinery',
                    ],
                    [
                        'label' => 'Manufacturing',
                        'value' => 'Manufacturing',
                    ],
                    [
                        'label' => 'Media',
                        'value' => 'Media',
                    ],
                    [
                        'label' => 'Metals & Mining',
                        'value' => 'Metals & Mining',
                    ],
                    [
                        'label' => 'Not for Profit',
                        'value' => 'Not for Profit',
                    ],
                    [
                        'label' => 'Oil & Gas',
                        'value' => 'Oil & Gas',
                    ],
                    [
                        'label' => 'Packaging & Paper',
                        'value' => 'Packaging & Paper',
                    ],
                    [
                        'label' => 'Private Equity & Principal Investors',
                        'value' => 'Private Equity & Principal Investors',
                    ],
                    [
                        'label' => 'Recreation',
                        'value' => 'Recreation',
                    ],
                    [
                        'label' => 'Real Estate',
                        'value' => 'Real Estate',
                    ],
                    [
                        'label' => 'Retail',
                        'value' => 'Retail',
                    ],
                    [
                        'label' => 'Semiconductors',
                        'value' => 'Semiconductors',
                    ],
                    [
                        'label' => 'Shipping',
                        'value' => 'Shipping',
                    ],
                    [
                        'label' => 'Social Sector',
                        'value' => 'Social Sector',
                    ],
                    [
                        'label' => 'Technology',
                        'value' => 'Technology',
                    ],
                    [
                        'label' => 'Telecommunications',
                        'value' => 'Telecommunications',
                    ],
                    [
                        'label' => 'Transportation',
                        'value' => 'Transportation',
                    ],
                    [
                        'label' => 'Utilities',
                        'value' => 'Utilities',
                    ],
                    [
                        'label' => 'Other',
                        'value' => 'Other',
                    ],
                ],
            ],
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companydescription' => [
            'fixed'    => true,
            'group'    => 'professional',
            'listable' => true,
            'object'   => 'company',
        ],
    ];

    public function __construct(
        private ColumnSchemaHelper $columnSchemaHelper,
        private ListModel $leadListModel,
        private CustomFieldColumn $customFieldColumn,
        private FieldSaveDispatcher $fieldSaveDispatcher,
        private LeadFieldRepository $leadFieldRepository,
        private FieldList $fieldList,
        private LeadFieldSaver $leadFieldSaver,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): LeadFieldRepository
    {
        return $this->leadFieldRepository;
    }

    public function getPermissionBase(): string
    {
        return 'lead:fields';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?LeadField
    {
        if (null === $id) {
            return new LeadField();
        }

        return parent::getEntity($id);
    }

    /**
     * @return LeadField[]|array<int,mixed>|iterable<LeadField>|\Doctrine\ORM\Internal\Hydration\IterableResult<LeadField>|Paginator<LeadField>|SimplePaginator<LeadField>
     */
    public function getEntities(array $args = [])
    {
        $repository = $this->em->getRepository(LeadField::class);
        \assert($repository instanceof LeadFieldRepository);

        return $repository->getEntities($args);
    }

    /**
     * @return array
     */
    public function getLeadFields()
    {
        return $this->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'f.object',
                        'expr'   => 'like',
                        'value'  => 'lead',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return LeadField[]
     */
    public function getLeadFieldCustomFields(): array
    {
        $forceFilter = [
            [
                'column' => $this->getRepository()->getTableAlias().'.object',
                'expr'   => 'like',
                'value'  => 'lead',
            ],
            [
                'column' => $this->getRepository()->getTableAlias().'.dateAdded',
                'expr'   => 'isNotNull',
            ],
        ];

        return $this->getEntities([
            'filter' => [
                'force' => $forceFilter,
            ],
            'ignore_paginator' => true,
        ]);
    }

    /**
     * @return mixed[]
     */
    public function getLeadFieldCustomFieldSchemaDetails(): array
    {
        $fields  = $this->getLeadFieldCustomFields();
        $columns = $this->columnSchemaHelper->setName('leads')->getColumns();

        $schemaDetails = [];
        foreach ($fields as $value) {
            if (!empty($columns[$value->getAlias()])) {
                $schemaDetails[$value->getAlias()] = $columns[$value->getAlias()];
            }
        }

        return $schemaDetails;
    }

    /**
     * @return array
     */
    public function getCompanyFields()
    {
        return $this->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'f.object',
                        'expr'   => 'like',
                        'value'  => 'company',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param LeadField $entity
     * @param bool      $unlock
     *
     * @throws AbortColumnCreateException
     * @throws AbortColumnUpdateException
     * @throws \Doctrine\DBAL\Exception
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function saveEntity($entity, $unlock = true): void
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadEntity']);
        }

        $this->setTimestamps($entity, $entity->isNew(), $unlock);

        if ('time' === $entity->getType()) {
            // time does not work well with list filters
            $entity->setIsListable(false);
        }

        // Save the entity now if it's an existing entity
        if (!$entity->isNew()) {
            $this->leadFieldSaver->saveLeadFieldEntity($entity, false);
        }

        try {
            $this->customFieldColumn->createLeadColumn($entity);
        } catch (CustomFieldLimitException $e) {
            // Convert to original Exception not to cause BC
            throw new \Doctrine\DBAL\Exception($this->translator->trans($e->getMessage()));
        }

        // Update order of the other fields.
        $this->reorderFieldsByEntity($entity);
    }

    /**
     * Build schema for each entity.
     *
     * @param array $entities
     * @param bool  $unlock
     *
     * @throws AbortColumnCreateException
     * @throws \Doctrine\DBAL\Exception
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function saveEntities($entities, $unlock = true): void
    {
        foreach ($entities as $entity) {
            $this->saveEntity($entity, $unlock);
        }
    }

    /**
     * @param object $entity
     *
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function deleteEntity($entity): void
    {
        parent::deleteEntity($entity);

        switch ($entity->getObject()) {
            case 'lead':
                $this->columnSchemaHelper->setName('leads')->dropColumn($entity->getAlias())->executeChanges();
                break;
            case 'company':
                $this->columnSchemaHelper->setName('companies')->dropColumn($entity->getAlias())->executeChanges();
                break;
        }
    }

    /**
     * Delete an array of entities.
     *
     * @param mixed[] $ids
     *
     * @return mixed[]
     *
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function deleteEntities($ids): array
    {
        $entities = parent::deleteEntities($ids);

        /** @var LeadField $entity */
        foreach ($entities as $entity) {
            switch ($entity->getObject()) {
                case 'lead':
                    $this->columnSchemaHelper->setName('leads')->dropColumn($entity->getAlias())->executeChanges();
                    break;
                case 'company':
                    $this->columnSchemaHelper->setName('companies')->dropColumn($entity->getAlias())->executeChanges();
                    break;
            }
        }

        return $entities;
    }

    /**
     * Is field used in segment filter?
     */
    public function isUsedField(LeadField $field): bool
    {
        return $this->leadListModel->isFieldUsed($field);
    }

    /**
     * Returns list of all segments that use $field.
     *
     * @return Paginator
     */
    public function getFieldSegments(LeadField $field)
    {
        return $this->leadListModel->getFieldSegments($field);
    }

    /**
     * Filter used field ids.
     */
    public function filterUsedFieldIds(array $ids): array
    {
        return array_filter($ids, fn ($id): bool => false === $this->isUsedField($this->getEntity($id)));
    }

    /**
     * Reorder fields based on passed entity position.
     */
    public function reorderFieldsByEntity($entity): void
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadEntity']);
        }

        $fields = $this->getRepository()->findBy([], ['order' => 'ASC']);
        $count  = 1;
        $order  = $entity->getOrder();
        $id     = $entity->getId();
        $hit    = false;
        foreach ($fields as $field) {
            if ($id !== $field->getId()) {
                if ($order === $field->getOrder()) {
                    if ($hit) {
                        $field->setOrder($count - 1);
                    } else {
                        $field->setOrder($count + 1);
                    }
                } else {
                    $field->setOrder($count);
                }
                $this->em->persist($field);
            } else {
                $hit = true;
            }
            ++$count;
        }
        $this->em->flush();
    }

    /**
     * Reorders fields by a list of field ids.
     *
     * @param int $start Number to start the order by (used for paginated reordering)
     */
    public function reorderFieldsByList(array $list, $start = 1): void
    {
        $fields = $this->getRepository()->findBy([], ['order' => 'ASC']);
        foreach ($fields as $field) {
            if (in_array($field->getId(), $list)) {
                $order = ((int) array_search($field->getId(), $list) + $start);
                $field->setOrder($order);
                $this->em->persist($field);
            }
        }
        $this->em->flush();
    }

    /**
     * Get list of custom field values for autopopulate fields.
     *
     * @param string $type
     * @param string $filter
     * @param int    $limit
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        /** @var LeadRepository $contactRepository */
        $contactRepository = $this->em->getRepository(Lead::class);

        return $contactRepository->getValueList($type, $filter, $limit);
    }

    /**
     * @param array $options
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadField']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(FieldType::class, $entity, $options);
    }

    /**
     * @return string|true
     */
    public function setFieldProperties(LeadField $entity, array $properties)
    {
        if (!empty($properties) && is_array($properties)) {
            $properties = InputHelper::clean($properties);
        } else {
            $properties = [];
        }

        // validate properties
        $type   = $entity->getType();
        $result = FormFieldHelper::validateProperties($type, $properties);
        if ($result[0]) {
            $entity->setProperties($properties);

            return true;
        }

        return $result[1];
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        switch ($action) {
            case 'pre_save':
                $action = LeadEvents::FIELD_PRE_SAVE;
                break;
            case 'post_save':
                $action = LeadEvents::FIELD_POST_SAVE;
                break;
            case 'pre_delete':
                $action = LeadEvents::FIELD_PRE_DELETE;
                break;
            case 'post_delete':
                $action = LeadEvents::FIELD_POST_DELETE;
                break;
        }

        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadField']);
        }

        if (null !== $event && !$event instanceof LeadFieldEvent) {
            throw new \RuntimeException('Event should be LeadFieldEvent|null.');
        }

        try {
            return $this->fieldSaveDispatcher->dispatchEvent($action, $entity, $isNew, $event);
        } catch (NoListenerException) {
            return $event;
        }
    }

    /**
     * @deprecated Use FieldList::getFieldList method instead
     *
     * @param bool|true $byGroup
     * @param bool|true $alphabetical
     * @param array     $filters
     *
     * @return mixed[]
     */
    public function getFieldList($byGroup = true, $alphabetical = true, $filters = ['isPublished' => true, 'object' => 'lead']): array
    {
        return $this->fieldList->getFieldList($byGroup, $alphabetical, $filters);
    }

    /**
     * @param string $object
     *
     * @return array
     */
    public function getPublishedFieldArrays($object = 'lead')
    {
        return $this->getEntities(
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
                            'value'  => $object,
                        ],
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
                'result_cache'   => new ResultCacheOptions(LeadField::CACHE_NAMESPACE),
            ]
        );
    }

    /**
     * @param string $object
     */
    public function getFieldListWithProperties($object = 'lead'): array
    {
        return $this->getFieldsProperties(['object' => $object]);
    }

    /**
     * @param mixed[] $filters
     *
     * @return mixed[]
     */
    public function getFieldsProperties(array $filters = []): array
    {
        $forceFilters = [];

        foreach ($filters as $col => $val) {
            $forceFilters[] = [
                'column' => "f.{$col}",
                'expr'   => is_array($val) ? 'in' : 'eq',
                'value'  => $val,
            ];
        }

        $contactFields = $this->getEntities(
            [
                'filter' => [
                    'force' => $forceFilters,
                ],
                'ignore_paginator' => true,
                'hydration_mode'   => 'hydrate_array',
            ]
        );

        $fields = [];
        foreach ($contactFields as $contactField) {
            $fields[$contactField['alias']] = [
                'label'        => $contactField['label'],
                'alias'        => $contactField['alias'],
                'type'         => $contactField['type'],
                'group'        => $contactField['group'],
                'object'       => $contactField['object'],
                'group_label'  => $this->translator->trans('mautic.lead.field.group.'.$contactField['group']),
                'defaultValue' => $contactField['defaultValue'],
                'properties'   => $contactField['properties'],
                'isPublished'  => $contactField['isPublished'],
            ];
        }

        return $fields;
    }

    /**
     * Get the fields for a specific group.
     *
     * @param array $filters
     */
    public function getGroupFields($group, $filters = ['isPublished' => true]): array
    {
        $forceFilters = [
            [
                'column' => 'f.group',
                'expr'   => 'eq',
                'value'  => $group,
            ],
        ];
        foreach ($filters as $col => $val) {
            $forceFilters[] = [
                'column' => "f.{$col}",
                'expr'   => 'eq',
                'value'  => $val,
            ];
        }
        // Get a list of custom form fields
        $fields = $this->getEntities([
            'filter' => [
                'force' => $forceFilters,
            ],
            'orderBy'    => 'f.order',
            'orderByDir' => 'asc',
        ]);

        $leadFields = [];

        foreach ($fields as $f) {
            $leadFields[$f->getAlias()] = $f->getLabel();
        }

        return $leadFields;
    }

    /**
     * Get the MySQL database type based on the field type
     * Use a static function so that it's accessible from DoctrineSubscriber
     * without causing a circular service injection error.
     *
     * @deprecated Use SchemaDefinition::getSchemaDefinition method instead
     *
     * @param bool $isUnique
     */
    public static function getSchemaDefinition($alias, $type, $isUnique = false): array
    {
        return SchemaDefinition::getSchemaDefinition($alias, $type, $isUnique);
    }

    public function getEntityByAlias($alias, $categoryAlias = null, $lang = null)
    {
        return $this->getRepository()->findOneByAlias($alias);
    }
}
