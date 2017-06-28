<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Mautic\CoreBundle\Doctrine\Helper\SchemaHelperFactory;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class FieldModel
 * {@inheritdoc}
 */
class FieldModel extends FormModel
{
    public static $coreFields = [
        // Listed according to $order for installation
        'title' => [
            'type'       => 'lookup',
            'properties' => ['list' => 'Mr|Mrs|Miss'],
            'fixed'      => true,
            'object'     => 'lead',
        ],
        'firstname' => [
            'fixed'  => true,
            'short'  => true,
            'object' => 'lead',
        ],
        'lastname' => [
            'fixed'  => true,
            'short'  => true,
            'object' => 'lead',
        ],
        'company' => [
            'fixed'  => true,
            'object' => 'lead',
        ],
        'position' => [
            'fixed'  => true,
            'object' => 'lead',
        ],
        'email' => [
            'type'   => 'email',
            'unique' => true,
            'fixed'  => true,
            'short'  => true,
            'object' => 'lead',
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
            'fixed'  => true,
            'object' => 'lead',
        ],
        'state' => [
            'type'   => 'region',
            'fixed'  => true,
            'object' => 'lead',
        ],
        'zipcode' => [
            'fixed'  => true,
            'object' => 'lead',
        ],
        'country' => [
            'type'   => 'country',
            'fixed'  => true,
            'object' => 'lead',
        ],
        'preferred_locale' => [
            'type'     => 'locale',
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
            'properties' => ['roundmode' => 4, 'precision' => 2],
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
        'googleplus' => [
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
            'type'   => 'email',
            'unique' => true,
            'fixed'  => true,
            'object' => 'company',
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
            'type'   => 'region',
            'fixed'  => true,
            'object' => 'company',
        ],
        'companyzipcode' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companycountry' => [
            'type'   => 'country',
            'fixed'  => true,
            'object' => 'company',
        ],
        'companyname' => [
            'fixed'    => true,
            'required' => true,
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
            'properties' => ['roundmode' => 4, 'precision' => 0],
            'group'      => 'professional',
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
            'properties' => ['roundmode' => 4, 'precision' => 2],
            'listable'   => true,
            'group'      => 'professional',
            'object'     => 'company',
        ],
        'companyindustry' => [
            'type'       => 'select',
            'group'      => 'professional',
            'properties' => ['list' => 'Agriculture|Apparel|Banking|Biotechnology|Chemicals|Communications|Construction|Education|Electronics|Energy|Engineering|Entertainment|Environmental|Finance|Food & Beverage|Government|Healthcare|Hospitality|Insurance|Machinery|Manufacturing|Media|Not for Profit|Recreation|Retail|Shipping|Technology|Telecommunications|Transportation|Utilities|Other'],
            'fixed'      => true,
            'object'     => 'company',
        ],
        'companydescription' => [
            'fixed'  => true,
            'group'  => 'professional',
            'object' => 'company',
        ],
    ];

    /**
     * @var SchemaHelperFactory
     */
    protected $schemaHelperFactory;

    /**
     * @var array
     */
    protected $uniqueIdentifierFields = [];

    /**
     * FieldModel constructor.
     *
     * @param SchemaHelperFactory $schemaHelperFactory
     */
    public function __construct(SchemaHelperFactory $schemaHelperFactory)
    {
        $this->schemaHelperFactory = $schemaHelperFactory;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadField');
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
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new LeadField();
        }

        $entity = parent::getEntity($id);

        return $entity;
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
        return $this->em->getRepository('MauticLeadBundle:LeadField')->getEntities($args);
    }

    /**
     * @param   $entity
     * @param   $unlock
     *
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadEntity']);
        }

        $isNew = ($entity->getId()) ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);
        $objects = ['lead' => 'leads', 'company' => 'companies'];
        $alias   = $entity->getAlias();
        $object  = $objects[$entity->getObject()];

        if ($isNew) {
            if (empty($alias)) {
                $alias = $entity->getName();
            }

            if (empty($object)) {
                $object = $objects[$entity->getObject()];
            }

            // clean the alias
            $alias = $this->cleanAlias($alias, 'f_', 25);

            // make sure alias is not already taken
            $repo      = $this->getRepository();
            $testAlias = $alias;
            $aliases   = $repo->getAliases($entity->getId(), false, true, $entity->getObject());
            $count     = (int) in_array($testAlias, $aliases);
            $aliasTag  = $count;

            while ($count) {
                $testAlias = $alias.$aliasTag;
                $count     = (int) in_array($testAlias, $aliases);
                ++$aliasTag;
            }

            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        $type = $entity->getType();
        if ($type == 'time') {
            //time does not work well with list filters
            $entity->setIsListable(false);
        }

        $isUnique = $entity->getIsUniqueIdentifier();

        //create the field as its own column in the leads table
        $leadsSchema = $this->schemaHelperFactory->getSchemaHelper('column', $object);
        if ($isNew || (!$isNew && !$leadsSchema->checkColumnExists($alias))) {
            $schemaDefinition = self::getSchemaDefinition($alias, $type, $isUnique);
            $leadsSchema->addColumn(
                $schemaDefinition
            );

            try {
                $leadsSchema->executeChanges();
                $isCreated = true;
            } catch (DriverException $e) {
                $this->logger->addWarning($e->getMessage());
                if ($e->getErrorCode() === 1118 /* ER_TOO_BIG_ROWSIZE */) {
                    $isCreated = false;
                    throw new DBALException($this->translator->trans('mautic.core.error.max.field'));
                } else {
                    throw $e;
                }
            }

            if ($isCreated === true) {
                $event = $this->dispatchEvent('pre_save', $entity, $isNew);
                $this->getRepository()->saveEntity($entity);
                $this->dispatchEvent('post_save', $entity, $isNew, $event);
            }

            // Update the unique_identifier_search index and add an index for this field
            /** @var \Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper $modifySchema */
            $modifySchema = $this->schemaHelperFactory->getSchemaHelper('index', $object);
            if ('string' == $schemaDefinition['type']) {
                try {
                    $modifySchema->addIndex([$alias], $alias.'_search');
                    $modifySchema->allowColumn($alias);
                    if ($isUnique) {
                        // Get list of current uniques
                        $uniqueIdentifierFields = $this->getUniqueIdentifierFields();

                        // Always use email
                        $indexColumns   = ['email'];
                        $indexColumns   = array_merge($indexColumns, array_keys($uniqueIdentifierFields));
                        $indexColumns[] = $alias;

                        // Only use three to prevent max key length errors
                        $indexColumns = array_slice($indexColumns, 0, 3);
                        $modifySchema->addIndex($indexColumns, 'unique_identifier_search');
                    }
                    $modifySchema->executeChanges();
                } catch (DriverException $e) {
                    if ($e->getErrorCode() === 1069 /* ER_TOO_MANY_KEYS */) {
                        $this->logger->addWarning($e->getMessage());
                    } else {
                        throw $e;
                    }
                }
            }
        }

        //update order of other fields
        $this->reorderFieldsByEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @param  $entity
     */
    public function deleteEntity($entity)
    {
        parent::deleteEntity($entity);

        $objects = ['lead' => 'leads', 'company' => 'companies'];
        $object  = $objects[$entity->getObject()];

        //remove the column from the leads table
        $leadsSchema = $this->schemaHelperFactory->getSchemaHelper('column', $object);
        $leadsSchema->dropColumn($entity->getAlias());
        $leadsSchema->executeChanges();
    }

    /**
     * Delete an array of entities.
     *
     * @param array $ids
     *
     * @return array
     */
    public function deleteEntities($ids)
    {
        $entities = parent::deleteEntities($ids);

        //remove the column from the leads table
        $leadsSchema = $this->schemaHelperFactory->getSchemaHelper('column', 'leads');
        foreach ($entities as $e) {
            $leadsSchema->dropColumn($e->getAlias());
        }
        $leadsSchema->executeChanges();
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
     * @param array $list
     * @param int   $start Number to start the order by (used for paginated reordering)
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
     * @param $type
     * @param $filter
     * @param $limit
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        return $this->em->getRepository('MauticLeadBundle:Lead')->getValueList($type, $filter, $limit);
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadField']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('leadfield', $entity, $options);
    }

    /**
     * @param $entity
     * @param properties
     *
     * @return bool
     */
    public function setFieldProperties(&$entity, $properties)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadEntity']);
        }

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
        } else {
            return $result[1];
        }
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

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::FIELD_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::FIELD_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::FIELD_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::FIELD_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadFieldEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param bool|true $byGroup
     * @param bool|true $alphabetical
     * @param array     $filters
     *
     * @return array
     */
    public function getFieldList($byGroup = true, $alphabetical = true, $filters = ['isPublished' => true, 'object' => 'lead'])
    {
        $forceFilters = [];
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
            if ($byGroup) {
                $fieldName                              = $this->translator->trans('mautic.lead.field.group.'.$f->getGroup());
                $leadFields[$fieldName][$f->getAlias()] = $f->getLabel();
            } else {
                $leadFields[$f->getAlias()] = $f->getLabel();
            }
        }

        if ($alphabetical) {
            // Sort the groups
            uksort($leadFields, 'strnatcmp');

            if ($byGroup) {
                // Sort each group by translation
                foreach ($leadFields as $group => &$fieldGroup) {
                    uasort($fieldGroup, 'strnatcmp');
                }
            }
        }

        return $leadFields;
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
     * @param array $filters
     *
     * @return mixed
     */
    public function getUniqueIdentifierFields($filters = [])
    {
        $filters['isPublished']       = isset($filters['isPublished']) ? $filters['isPublished'] : true;
        $filters['isUniqueIdentifer'] = isset($filters['isUniqueIdentifer']) ? $filters['isUniqueIdentifer'] : true;
        $filters['object']            = isset($filters['object']) ? $filters['object'] : 'lead';

        $key = base64_encode(json_encode($filters));
        if (!isset($this->uniqueIdentifierFields[$key])) {
            $this->uniqueIdentifierFields[$key] = $this->getFieldList(false, true, $filters);
        }

        return $this->uniqueIdentifierFields[$key];
    }

    /**
     * Get the MySQL database type based on the field type
     * Use a static function so that it's accessible from DoctrineSubscriber
     * without causing a circular service injection error.
     *
     * @param      $alias
     * @param      $type
     * @param bool $isUnique
     *
     * @return array
     */
    public static function getSchemaDefinition($alias, $type, $isUnique = false)
    {
        // Unique is always a string in order to control index length
        if ($isUnique) {
            return [
                'name'    => $alias,
                'type'    => 'string',
                'options' => [
                    'notnull' => false,
                ],
            ];
        }

        switch ($type) {
            case 'datetime':
            case 'date':
            case 'time':
            case 'boolean':
                $schemaType = $type;
                break;
            case 'number':
                $schemaType = 'float';
                break;
            case 'timezone':
            case 'locale':
            case 'country':
            case 'email':
            case 'lookup':
            case 'select':
            case 'multiselect':
            case 'region':
            case 'tel':
                $schemaType = 'string';
                break;
            case 'text':
                $schemaType = (strpos($alias, 'description') !== false) ? 'text' : 'string';
                break;
            default:
                $schemaType = 'text';
        }

        return [
            'name'    => $alias,
            'type'    => $schemaType,
            'options' => ['notnull' => false],
        ];
    }
}
