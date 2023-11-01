<?php

namespace Mautic\LeadBundle\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Field\CustomFieldColumn;
use Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\CustomFieldLimitException;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Field\LeadFieldSaver;
use Mautic\LeadBundle\Field\SchemaDefinition;
use Mautic\LeadBundle\Form\Type\FieldType;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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
            'fixed'      => true,
            'listable'   => true,
            'object'     => 'lead',
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
                        'label' => 'Agriculture',
                        'value' => 'Agriculture',
                    ],
                    [
                        'label' => 'Apparel',
                        'value' => 'Apparel',
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
                        'label' => 'Not for Profit',
                        'value' => 'Not for Profit',
                    ],
                    [
                        'label' => 'Recreation',
                        'value' => 'Recreation',
                    ],
                    [
                        'label' => 'Retail',
                        'value' => 'Retail',
                    ],
                    [
                        'label' => 'Shipping',
                        'value' => 'Shipping',
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
            'fixed'      => true,
            'listable'   => true,
            'object'     => 'company',
        ],
        'companydescription' => [
            'fixed'    => true,
            'group'    => 'professional',
            'listable' => true,
            'object'   => 'company',
        ],
    ];

    /**
     * @var ColumnSchemaHelper
     */
    private $columnSchemaHelper;

    /**
     * @var CustomFieldColumn
     */
    private $customFieldColumn;

    /**
     * @var FieldSaveDispatcher
     */
    private $fieldSaveDispatcher;

    /**
     * @var LeadFieldRepository
     */
    private $leadFieldRepository;

    /**
     * @var ListModel
     */
    private $leadListModel;

    /**
     * @var FieldsWithUniqueIdentifier
     */
    private $fieldsWithUniqueIdentifier;

    /**
     * @var FieldList
     */
    private $fieldList;

    /**
     * @var LeadFieldSaver
     */
    private $leadFieldSaver;

    public function __construct(
        ColumnSchemaHelper $columnSchemaHelper,
        ListModel $leadListModel,
        CustomFieldColumn $customFieldColumn,
        FieldSaveDispatcher $fieldSaveDispatcher,
        LeadFieldRepository $leadFieldRepository,
        FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier,
        FieldList $fieldList,
        LeadFieldSaver $leadFieldSaver
    ) {
        $this->columnSchemaHelper         = $columnSchemaHelper;
        $this->leadListModel              = $leadListModel;
        $this->customFieldColumn          = $customFieldColumn;
        $this->fieldSaveDispatcher        = $fieldSaveDispatcher;
        $this->leadFieldRepository        = $leadFieldRepository;
        $this->fieldsWithUniqueIdentifier = $fieldsWithUniqueIdentifier;
        $this->fieldList                  = $fieldList;
        $this->leadFieldSaver             = $leadFieldSaver;
    }

    /**
     * @return LeadFieldRepository
     */
    public function getRepository()
    {
        return $this->leadFieldRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:fields';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return LeadField|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new LeadField();
        }

        return parent::getEntity($id);
    }

    /**
     * Returns lead custom fields.
     *
     * @param $args
     *
     * @return array
     */
    public function getEntities(array $args = [])
    {
        return $this->em->getRepository(LeadField::class)->getEntities($args);
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
     * @throws DBALException
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadEntity']);
        }

        $this->setTimestamps($entity, $entity->isNew(), $unlock);

        if ('time' === $entity->getType()) {
            //time does not work well with list filters
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
            throw new DBALException($this->translator->trans($e->getMessage()));
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
     * @return array|void
     *
     * @throws AbortColumnCreateException
     * @throws DBALException
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function saveEntities($entities, $unlock = true)
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
    public function deleteEntity($entity)
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
     * @param array $ids
     *
     * @return array
     *
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function deleteEntities($ids)
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
     *
     * @return bool
     */
    public function isUsedField(LeadField $field)
    {
        return $this->leadListModel->isFieldUsed($field);
    }

    /**
     * Returns list of all segments that use $field.
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getFieldSegments(LeadField $field)
    {
        return $this->leadListModel->getFieldSegments($field);
    }

    /**
     * Filter used field ids.
     *
     * @return array
     */
    public function filterUsedFieldIds(array $ids)
    {
        return array_filter($ids, function ($id) {
            return false === $this->isUsedField($this->getEntity($id));
        });
    }

    /**
     * Reorder fields based on passed entity position.
     *
     * @param $entity
     */
    public function reorderFieldsByEntity($entity)
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
    public function reorderFieldsByList(array $list, $start = 1)
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
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
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
     * @param $properties
     *
     * @return bool
     */
    public function setFieldProperties(LeadField $entity, array $properties)
    {
        if (!empty($properties) && is_array($properties)) {
            $properties = InputHelper::clean($properties);
        } else {
            $properties = [];
        }

        //validate properties
        $type   = $entity->getType();
        $result = FormFieldHelper::validateProperties($type, $properties);
        if ($result[0]) {
            $entity->setProperties($properties);

            return true;
        }

        return $result[1];
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadField']);
        }

        return $this->fieldSaveDispatcher->dispatchEventBc($action, $entity, $isNew, $event);
    }

    /**
     * @deprecated Use FieldList::getFieldList method instead
     *
     * @param bool|true $byGroup
     * @param bool|true $alphabetical
     * @param array     $filters
     *
     * @return array
     */
    public function getFieldList($byGroup = true, $alphabetical = true, $filters = ['isPublished' => true, 'object' => 'lead'])
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
            ]
        );
    }

    /**
     * @param string $object
     *
     * @return array
     */
    public function getFieldListWithProperties($object = 'lead')
    {
        $forceFilters[] = [
            'column' => 'f.object',
            'expr'   => 'eq',
            'value'  => $object,
        ];
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
     * @param       $group
     * @param array $filters
     *
     * @return array
     */
    public function getGroupFields($group, $filters = ['isPublished' => true])
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
     * Retrieves a list of published fields that are unique identifers.
     *
     * @deprecated to be removed in 3.0
     *
     * @return array
     */
    public function getUniqueIdentiferFields($filters = [])
    {
        return $this->getUniqueIdentifierFields($filters);
    }

    /**
     * Retrieves a list of published fields that are unique identifers.
     *
     * @deprecated Use FieldsWithUniqueIdentifier::getFieldsWithUniqueIdentifier method instead
     *
     * @param array $filters
     *
     * @return mixed
     */
    public function getUniqueIdentifierFields($filters = [])
    {
        return $this->fieldsWithUniqueIdentifier->getFieldsWithUniqueIdentifier($filters);
    }

    /**
     * Get the MySQL database type based on the field type
     * Use a static function so that it's accessible from DoctrineSubscriber
     * without causing a circular service injection error.
     *
     * @deprecated Use SchemaDefinition::getSchemaDefinition method instead
     *
     * @param      $alias
     * @param      $type
     * @param bool $isUnique
     *
     * @return array
     */
    public static function getSchemaDefinition($alias, $type, $isUnique = false)
    {
        return SchemaDefinition::getSchemaDefinition($alias, $type, $isUnique);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityByAlias($alias, $categoryAlias = null, $lang = null)
    {
        return $this->getRepository()->findOneByAlias($alias);
    }
}
