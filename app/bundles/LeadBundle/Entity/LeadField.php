<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\LeadBundle\Field\DTO\CustomFieldObject;
use Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeyword;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *   attributes={
 *     "security"="false",
 *     "normalization_context"={
 *       "groups"={
 *         "leadfield:read"
 *        },
 *       "swagger_definition_name"="Read"
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "leadfield:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class LeadField extends FormEntity implements CacheInvalidateInterface, UuidInterface
{
    use UuidTrait;
    public const CACHE_NAMESPACE    = 'LeadField';

    /**
     * @var int
     */
    private $id;

    private bool $isCloned = false;

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
     * @var string|null
     */
    private $group = 'core';

    /**
     * @var string|null
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
    private $isShortVisible = false;

    /**
     * @var bool
     */
    private $isListable = true;

    /**
     * @var bool
     */
    private $isPubliclyUpdatable = false;

    /**
     * @var bool|null
     */
    private $isUniqueIdentifer = false;

    /**
     * Workaround for incorrectly spelled $isUniqueIdentifer.
     *
     * @var bool
     */
    private $isUniqueIdentifier = false;

    private ?int $charLengthLimit = 64;

    /**
     * @var int|null
     */
    private $order = 1;

    /**
     * @var string|null
     */
    private $object = 'lead';

    /**
     * @var array
     */
    private $properties = [];

    private bool $isIndex = false;

    /**
     * The column in lead_fields table was not created yet if this property is true.
     * Entity cannot be published and we cannot work with it until column is created.
     *
     * @var bool
     */
    private $columnIsNotCreated = false;

    /**
     * This property contains an original value for $isPublished.
     * $isPublished is always set on false if $columnIsNotCreated is true.
     *
     * @var bool
     */
    private $originalIsPublishedValue = false;

    /**
     * @var CustomFieldObject
     */
    private $customFieldObject;

    public function __clone()
    {
        $this->id         = null;
        $this->isCloned   = true;
        $this->order      =  0;
        $this->isFixed    = false;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->addLifecycleEvent('identifierWorkaround', 'postLoad');

        $builder->setTable('lead_fields')
            ->setCustomRepositoryClass(LeadFieldRepository::class)
            ->addIndex(['object', 'field_order', 'is_published'], 'idx_object_field_order_is_published');

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
            ->nullable(false)
            ->option('default', false)
            ->build();

        $builder->createField('isListable', 'boolean')
            ->columnName('is_listable')
            ->build();

        $builder->createField('isPubliclyUpdatable', 'boolean')
            ->columnName('is_publicly_updatable')
            ->build();

        $builder->addNullableField('isUniqueIdentifer', 'boolean', 'is_unique_identifer');

        $builder->createField('isIndex', 'boolean')
            ->columnName('is_index')
            ->option('default', false)
            ->nullable(false)
            ->build();

        $builder->createField('charLengthLimit', 'integer')
            ->columnName('char_length_limit')
            ->nullable()
            ->build();

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

        $builder->createField('columnIsNotCreated', 'boolean')
            ->columnName('column_is_not_created')
            ->option('default', false)
            ->build();

        $builder->createField('originalIsPublishedValue', 'boolean')
            ->columnName('original_is_published_value')
            ->option('default', false)
            ->build();

        static::addUuidField($builder);
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('label', new Assert\NotBlank(
            ['message' => 'mautic.lead.field.label.notblank']
        ));

        $metadata->addPropertyConstraint('label', new Assert\Length([
            'max'        => 191,
            'maxMessage' => 'mautic.lead.field.label.maxlength',
        ]));

        $metadata->addConstraint(new UniqueEntity([
            'fields'  => ['alias'],
            'message' => 'mautic.lead.field.alias.unique',
        ]));

        $metadata->addConstraint(new Assert\Callback([
            'callback' => function (LeadField $field, ExecutionContextInterface $context): void {
                $violations = $context->getValidator()->validate($field, [new FieldAliasKeyword()]);

                if ($violations->count() > 0) {
                    $context->buildViolation($violations->get(0)->getMessage())
                        ->atPath('alias')
                        ->addViolation();
                }
            },
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
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
                    'isFixed',
                    'isListable',
                    'isVisible',
                    'isVisible',
                    'isShortVisible',
                    'isUniqueIdentifier',
                    'isPubliclyUpdatable',
                    'properties',
                    'isIndex',
                    'charLengthLimit',
                ]
            )
            ->build();
    }

    public function setId(?int $id = null): void
    {
        $this->id = $id;
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

    public function getIsCloned(): bool
    {
        return $this->isCloned;
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
     * @param string|array<string> $defaultValue
     *
     * @return LeadField
     */
    public function setDefaultValue($defaultValue)
    {
        $defaultValue = is_array($defaultValue) ? implode('|', $defaultValue) : $defaultValue;
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
     * @param mixed[] $properties
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
     * @return mixed[]
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

    public function setCharLengthLimit(?int $charLengthLimit): LeadField
    {
        $this->isChanged('charLengthLimit', $charLengthLimit);
        $this->charLengthLimit = $charLengthLimit;

        return $this;
    }

    public function getCharLengthLimit(): ?int
    {
        return $this->charLengthLimit;
    }

    public function getCustomFieldObject(): string
    {
        if (!$this->customFieldObject) {
            $this->customFieldObject = new CustomFieldObject($this);
        }

        return $this->customFieldObject->getObject();
    }

    /**
     * Set object.
     *
     * @param string $object
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

    public function setIsShortVisible(?bool $isShortVisible): self
    {
        $isShortVisible = $isShortVisible ?? false;
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
        if ($isUniqueIdentifer) {
            $this->isIndex = true;
        }

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
    public function setGroup($group): void
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
    public function setIsPubliclyUpdatable($isPubliclyUpdatable): void
    {
        $this->isPubliclyUpdatable = (bool) $isPubliclyUpdatable;
    }

    /**
     * Workaround for mispelled isUniqueIdentifer.
     */
    public function identifierWorkaround(): void
    {
        $this->isUniqueIdentifier = $this->isUniqueIdentifer;
    }

    public function isNew(): bool
    {
        return $this->getId() ? false : true;
    }

    /**
     * @return bool
     */
    public function getColumnIsNotCreated()
    {
        return $this->columnIsNotCreated;
    }

    public function setColumnIsNotCreated(): void
    {
        $this->columnIsNotCreated       = true;
        $this->originalIsPublishedValue = $this->getIsPublished();
        $this->setIsPublished(false);
    }

    public function setColumnWasCreated(): void
    {
        $this->columnIsNotCreated = false;
        $this->setIsPublished($this->getOriginalIsPublishedValue());
    }

    public function disablePublishChange(): bool
    {
        return 'email' === $this->getAlias() || $this->getColumnIsNotCreated();
    }

    public function getOriginalIsPublishedValue(): bool
    {
        return (bool) $this->originalIsPublishedValue;
    }

    public function getCacheNamespacesToDelete(): array
    {
        return [self::CACHE_NAMESPACE];
    }

    public function isIsIndex(): bool
    {
        return $this->isIndex;
    }

    public function setIsIndex(?bool $indexable): void
    {
        $this->isIndex = $indexable ?? false;
    }
}
