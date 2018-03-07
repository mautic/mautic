<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class LeadField.
 */
class LeadField extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $type = 'text';

    /**
     * @var string
     */
    private $group = 'core';

    /**
     * @var string
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $isRequired = false;

    /**
     * @var bool
     */
    private $isFixed = false;

    /**
     * @var bool
     */
    private $isVisible = true;

    /**
     * @var bool
     */
    private $isShortVisible = true;

    /**
     * @var bool
     */
    private $isListable = true;

    /**
     * @var bool
     */
    private $isPubliclyUpdatable = false;

    /**
     * @var bool
     */
    private $isUniqueIdentifer = false;

    /**
     * Workaround for incorrectly spelled $isUniqueIdentifer.
     *
     * @var bool
     */
    private $isUniqueIdentifier = false;

    /**
     * @var int
     */
    private $order = 1;

    /**
     * @var string
     */
    private $object = 'lead';

    /**
     * @var array
     */
    private $properties = [];

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->addLifecycleEvent('identifierWorkaround', 'postLoad');

        $builder->setTable('lead_fields')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\LeadFieldRepository')
            ->addIndex(['object'], 'search_by_object');

        $builder->addId();

        $builder->addField('label', 'string');

        $builder->addField('alias', 'string');

        $builder->createField('type', 'string')
            ->length(50)
            ->build();

        $builder->createField('group', 'string')
            ->columnName('field_group')
            ->nullable()
            ->build();

        $builder->createField('defaultValue', 'string')
            ->columnName('default_value')
            ->nullable()
            ->build();

        $builder->createField('isRequired', 'boolean')
            ->columnName('is_required')
            ->build();

        $builder->createField('isFixed', 'boolean')
            ->columnName('is_fixed')
            ->build();

        $builder->createField('isVisible', 'boolean')
            ->columnName('is_visible')
            ->build();

        $builder->createField('isShortVisible', 'boolean')
            ->columnName('is_short_visible')
            ->build();

        $builder->createField('isListable', 'boolean')
            ->columnName('is_listable')
            ->build();

        $builder->createField('isPubliclyUpdatable', 'boolean')
            ->columnName('is_publicly_updatable')
            ->build();

        $builder->addNullableField('isUniqueIdentifer', 'boolean', 'is_unique_identifer');

        $builder->createField('order', 'integer')
            ->columnName('field_order')
            ->nullable()
            ->build();

        $builder->createField('object', 'string')
            ->nullable()
            ->build();

        $builder->createField('properties', 'array')
            ->nullable()
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('label', new Assert\NotBlank(
            ['message' => 'mautic.lead.field.label.notblank']
        ));

        $metadata->addConstraint(new UniqueEntity([
            'fields'  => ['alias'],
            'message' => 'mautic.lead.field.alias.unique',
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('leadField')
            ->addListProperties(
                [
                    'id',
                    'label',
                    'alias',
                    'type',
                    'group',
                    'order',
                    'object',
                ]
            )
            ->addProperties(
                [
                    'defaultValue',
                    'isRequired',
                    'isPubliclyUpdatable',
                    'isUniqueIdentifier',
                    'properties',
                ]
            )
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set label.
     *
     * @param string $label
     *
     * @return LeadField
     */
    public function setLabel($label)
    {
        $this->isChanged('label', $label);
        $this->label = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Proxy function to setLabel().
     *
     * @param string $label
     *
     * @return LeadField
     */
    public function setName($label)
    {
        $this->isChanged('label', $label);

        return $this->setLabel($label);
    }

    /**
     * Proxy function for getLabel().
     *
     * @return string
     */
    public function getName()
    {
        return $this->getLabel();
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return LeadField
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set defaultValue.
     *
     * @param string $defaultValue
     *
     * @return LeadField
     */
    public function setDefaultValue($defaultValue)
    {
        $this->isChanged('defaultValue', $defaultValue);
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * Get defaultValue.
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set isRequired.
     *
     * @param bool $isRequired
     *
     * @return LeadField
     */
    public function setIsRequired($isRequired)
    {
        $this->isChanged('isRequired', $isRequired);
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * Get isRequired.
     *
     * @return bool
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * Proxy to getIsRequired().
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->getIsRequired();
    }

    /**
     * Set isFixed.
     *
     * @param bool $isFixed
     *
     * @return LeadField
     */
    public function setIsFixed($isFixed)
    {
        $this->isFixed = $isFixed;

        return $this;
    }

    /**
     * Get isFixed.
     *
     * @return bool
     */
    public function getIsFixed()
    {
        return $this->isFixed;
    }

    /**
     * Proxy to getIsFixed().
     *
     * @return bool
     */
    public function isFixed()
    {
        return $this->getIsFixed();
    }

    /**
     * Set properties.
     *
     * @param string $properties
     *
     * @return LeadField
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);
        $this->properties = $properties;

        return $this;
    }

    /**
     * Get properties.
     *
     * @return string
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set order.
     *
     * @param int $order
     *
     * @return LeadField
     */
    public function setOrder($order)
    {
        $this->isChanged('order', $order);
        $this->order = $order;

        return $this;
    }

    /**
     * Get object.
     *
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set object.
     *
     * @param int $object
     *
     * @return LeadField
     */
    public function setObject($object)
    {
        $this->isChanged('object', $object);
        $this->object = $object;

        return $this;
    }

    /**
     * Get order.
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set isVisible.
     *
     * @param bool $isVisible
     *
     * @return LeadField
     */
    public function setIsVisible($isVisible)
    {
        $this->isChanged('isVisible', $isVisible);
        $this->isVisible = $isVisible;

        return $this;
    }

    /**
     * Get isVisible.
     *
     * @return bool
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }

    /**
     * Proxy to getIsVisible().
     *
     * @return bool
     */
    public function isVisible()
    {
        return $this->getIsVisible();
    }

    /**
     * Set isShortVisible.
     *
     * @param bool $isShortVisible
     *
     * @return LeadField
     */
    public function setIsShortVisible($isShortVisible)
    {
        $this->isChanged('isShortVisible', $isShortVisible);
        $this->isShortVisible = $isShortVisible;

        return $this;
    }

    /**
     * Get isShortVisible.
     *
     * @return bool
     */
    public function getIsShortVisible()
    {
        return $this->isShortVisible;
    }

    /**
     * Proxy to getIsShortVisible().
     *
     * @return bool
     */
    public function isShortVisible()
    {
        return $this->getIsShortVisible();
    }

    /**
     * Get the unique identifer state of the field.
     *
     * @return bool
     */
    public function getIsUniqueIdentifer()
    {
        return $this->isUniqueIdentifer;
    }

    /**
     * Set the unique identifer state of the field.
     *
     * @param mixed $isUniqueIdentifer
     *
     * @return LeadField
     */
    public function setIsUniqueIdentifer($isUniqueIdentifer)
    {
        $this->isUniqueIdentifer = $this->isUniqueIdentifier = $isUniqueIdentifer;

        return $this;
    }

    /**
     * Wrapper for incorrectly spelled setIsUniqueIdentifer.
     *
     * @return bool
     */
    public function getIsUniqueIdentifier()
    {
        return $this->getIsUniqueIdentifer();
    }

    /**
     * Wrapper for incorrectly spelled setIsUniqueIdentifer.
     *
     * @param mixed $isUniqueIdentifier
     *
     * @return LeadField
     */
    public function setIsUniqueIdentifier($isUniqueIdentifier)
    {
        return $this->setIsUniqueIdentifer($isUniqueIdentifier);
    }

    /**
     * Set alias.
     *
     * @param string $alias
     *
     * @return LeadField
     */
    public function setAlias($alias)
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set isListable.
     *
     * @param bool $isListable
     *
     * @return LeadField
     */
    public function setIsListable($isListable)
    {
        $this->isChanged('isListable', $isListable);
        $this->isListable = $isListable;

        return $this;
    }

    /**
     * Get isListable.
     *
     * @return bool
     */
    public function getIsListable()
    {
        return $this->isListable;
    }

    /**
     * Proxy to getIsListable().
     *
     * @return bool
     */
    public function isListable()
    {
        return $this->getIsListable();
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return mixed
     */
    public function getIsPubliclyUpdatable()
    {
        return $this->isPubliclyUpdatable;
    }

    /**
     * @param mixed $isPubliclyUpdatable
     */
    public function setIsPubliclyUpdatable($isPubliclyUpdatable)
    {
        $this->isPubliclyUpdatable = (bool) $isPubliclyUpdatable;
    }

    /**
     * Workaround for mispelled isUniqueIdentifer.
     */
    public function identifierWorkaround()
    {
        $this->isUniqueIdentifier = $this->isUniqueIdentifer;
    }
}
