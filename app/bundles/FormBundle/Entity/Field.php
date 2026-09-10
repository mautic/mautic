<?php

namespace Mautic\FormBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\FormBundle\ProgressiveProfiling\DisplayManager;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('form:forms:viewown')"),
        new Post(security: "is_granted('form:forms:create')"),
        new Get(security: "is_granted('form:forms:viewown', object)"),
        new Put(security: "is_granted('form:forms:editown', object)"),
        new Patch(security: "is_granted('form:forms:editother', object)"),
        new Delete(security: "is_granted('form:forms:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['field:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['field:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity(repositoryClass: FieldRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['type'], name: 'form_field_type_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Field implements UuidInterface
{
    use UuidTrait;

    public const TABLE_NAME  = 'form_fields';

    public const ENTITY_NAME = 'form_field';

    /**
     * @var int
     */
    #[Groups(['field:read', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(type: Types::TEXT)]
    private $label;

    /**
     * @var bool|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'show_label', type: Types::BOOLEAN, nullable: true)]
    private $showLabel = true;

    /**
     * @var string
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(type: Types::STRING, length: 191)]
    private $alias;

    /**
     * @var string
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(type: Types::STRING, length: 191)]
    private $type;

    /**
     * @var bool
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'is_custom', type: Types::BOOLEAN)]
    private $isCustom = false;

    /**
     * @var array
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'custom_parameters', type: Types::ARRAY, nullable: true)]
    private $customParameters = [];

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'default_value', type: Types::TEXT, nullable: true)]
    private $defaultValue;

    /**
     * @var bool
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'is_required', type: Types::BOOLEAN)]
    private $isRequired = false;

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'validation_message', type: Types::TEXT, nullable: true)]
    private $validationMessage;

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'help_message', type: Types::TEXT, nullable: true)]
    private $helpMessage;

    /**
     * @var int|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'field_order', type: Types::INTEGER, nullable: true)]
    private $order = 0;

    /**
     * @var array
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private $properties = [];

    /**
     * @var array
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private $validation = [];

    /**
     * @var array<string,mixed>|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(type: 'json', nullable: true)]
    private $conditions = [];

    /**
     * @var Form|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    private $form;

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'label_attr', type: Types::STRING, length: 191, nullable: true)]
    private $labelAttributes;

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'input_attr', type: Types::STRING, length: 191, nullable: true)]
    private $inputAttributes;

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'container_attr', type: Types::STRING, length: 191, nullable: true)]
    private $containerAttributes;

    /**
     * @var string|null
     *
     * @deprecated, to be removed in Mautic 4. Use mappedObject and mappedField instead.
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'lead_field', type: Types::STRING, length: 191, nullable: true)]
    private $leadField;

    /**
     * @var bool|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'save_result', type: Types::BOOLEAN, nullable: true)]
    private $saveResult = true;

    /**
     * @var bool|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'is_auto_fill', type: Types::BOOLEAN, nullable: true)]
    private $isAutoFill = false;

    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'is_read_only', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isReadOnly = false;

    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'field_width', type: Types::STRING, length: 50, options: ['default' => '100%'])]
    private string $fieldWidth = '100%';

    /**
     * @var array
     */
    private $changes;

    private $sessionId;

    /**
     * @var bool|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'show_when_value_exists', type: Types::BOOLEAN, nullable: true)]
    private $showWhenValueExists;

    /**
     * @var int|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'show_after_x_submissions', type: Types::INTEGER, nullable: true)]
    private $showAfterXSubmissions;

    /**
     * @var bool|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'always_display', type: Types::BOOLEAN, nullable: true)]
    private $alwaysDisplay;

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'parent_id', type: 'string', length: 191, nullable: true)]
    private $parent;

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'mapped_object', type: Types::STRING, length: 191, nullable: true)]
    private $mappedObject;

    /**
     * @var string|null
     */
    #[Groups(['field:read', 'field:write', 'form:read', 'campaign:read', 'email:read'])]
    #[ORM\Column(name: 'mapped_field', type: Types::STRING, length: 191, nullable: true)]
    private $mappedField;

    public ?int $deletedId = null;

    public function __clone()
    {
        $this->id   = null;
        $this->form = null;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createManyToOne('form', 'Form')
            ->inversedBy('fields')
            ->addJoinColumn('form_id', 'id', false, false, 'CASCADE')
            ->isOwnershipParent()
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('form')
            ->addProperties(
                [
                    'id',
                    'label',
                    'showLabel',
                    'alias',
                    'type',
                    'defaultValue',
                    'isRequired',
                    'validationMessage',
                    'helpMessage',
                    'order',
                    'properties',
                    'validation',
                    'parent',
                    'conditions',
                    'labelAttributes',
                    'inputAttributes',
                    'containerAttributes',
                    'leadField', // @deprecated, to be removed in Mautic 4. Use mappedObject and mappedField instead.
                    'saveResult',
                    'isAutoFill',
                    'isReadOnly',
                    'mappedObject',
                    'mappedField',
                    'fieldWidth',
                ]
            )
            ->build();
    }

    /**
     * @param mixed $val
     */
    private function isChanged(string $prop, $val): void
    {
        if ($this->{$prop} != $val) {
            $this->changes[$prop] = [$this->{$prop}, $val];
        }
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $label
     */
    public function setLabel($label): static
    {
        $this->isChanged('label', $label);
        $this->label = $label;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias): static
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $type
     */
    public function setType($type): static
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $defaultValue
     */
    public function setDefaultValue($defaultValue): static
    {
        $this->isChanged('defaultValue', $defaultValue);
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param bool $isRequired
     */
    public function setIsRequired($isRequired): static
    {
        $this->isChanged('isRequired', $isRequired);
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * Proxy function to getIsRequired.
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * @param int $order
     */
    public function setOrder($order): static
    {
        $this->isChanged('order', $order);
        $this->order = $order;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param array $properties
     */
    public function setProperties($properties): static
    {
        $this->isChanged('properties', $properties);
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $validation
     */
    public function setValidation($validation): static
    {
        $this->isChanged('validation', $validation);
        $this->validation = $validation;

        return $this;
    }

    /**
     * @return array
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * @param string $validationMessage
     */
    public function setValidationMessage($validationMessage): static
    {
        $this->isChanged('validationMessage', $validationMessage);
        $this->validationMessage = $validationMessage;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getValidationMessage()
    {
        return $this->validationMessage;
    }

    public function setForm(Form $form): static
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return Form|null
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param string $labelAttributes
     */
    public function setLabelAttributes($labelAttributes): static
    {
        $this->isChanged('labelAttributes', $labelAttributes);
        $this->labelAttributes = $labelAttributes;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabelAttributes()
    {
        return $this->labelAttributes;
    }

    /**
     * @param string $inputAttributes
     */
    public function setInputAttributes($inputAttributes): static
    {
        $this->isChanged('inputAttributes', $inputAttributes);
        $this->inputAttributes = $inputAttributes;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInputAttributes()
    {
        return $this->inputAttributes;
    }

    /**
     * @return string|null
     */
    public function getContainerAttributes()
    {
        return $this->containerAttributes;
    }

    public function setContainerAttributes($containerAttributes): static
    {
        $this->containerAttributes = $containerAttributes;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function convertToArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param bool $showLabel
     */
    public function setShowLabel($showLabel): static
    {
        $this->isChanged('showLabel', $showLabel);
        $this->showLabel = $showLabel;

        return $this;
    }

    /**
     * @return bool
     */
    public function getShowLabel()
    {
        return $this->showLabel;
    }

    /**
     * Proxy function to getShowLabel().
     *
     * @return bool
     */
    public function showLabel()
    {
        return $this->showLabel;
    }

    /**
     * @param string $helpMessage
     */
    public function setHelpMessage($helpMessage): static
    {
        $this->isChanged('helpMessage', $helpMessage);
        $this->helpMessage = $helpMessage;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHelpMessage()
    {
        return $this->helpMessage;
    }

    /**
     * @param bool $isCustom
     */
    public function setIsCustom($isCustom): static
    {
        $this->isCustom = $isCustom;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsCustom()
    {
        return $this->isCustom;
    }

    /**
     * Proxy function to getIsCustom().
     *
     * @return bool
     */
    public function isCustom()
    {
        return $this->isCustom;
    }

    /**
     * @param array $customParameters
     */
    public function setCustomParameters($customParameters): static
    {
        $this->customParameters = $customParameters;

        return $this;
    }

    /**
     * @return array
     */
    public function getCustomParameters()
    {
        return $this->customParameters;
    }

    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param mixed $sessionId
     */
    public function setSessionId($sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @deprecated, to be removed in Mautic 4. Use mappedObject and mappedField instead.
     *
     * @return string|null
     */
    public function getLeadField()
    {
        return $this->leadField;
    }

    /**
     * @deprecated, to be removed in Mautic 4. Use mappedObject and mappedField instead.
     *
     * @param mixed $leadField
     */
    public function setLeadField($leadField): void
    {
        $this->leadField = $leadField;
    }

    /**
     * @return bool|null
     */
    public function getSaveResult()
    {
        return $this->saveResult;
    }

    /**
     * @param mixed $saveResult
     */
    public function setSaveResult($saveResult): void
    {
        $this->saveResult = $saveResult;
    }

    /**
     * @return bool
     */
    public function getIsAutoFill()
    {
        return $this->isAutoFill;
    }

    /**
     * @param mixed $isAutoFill
     */
    public function setIsAutoFill($isAutoFill): void
    {
        $this->isAutoFill = $isAutoFill;
    }

    /**
     * @return bool|null
     */
    public function getShowWhenValueExists()
    {
        return $this->showWhenValueExists;
    }

    /**
     * @param bool $showWhenValueExists
     */
    public function setShowWhenValueExists($showWhenValueExists): void
    {
        $this->showWhenValueExists = $showWhenValueExists;
    }

    /**
     * @return int|null
     */
    public function getShowAfterXSubmissions()
    {
        return $this->showAfterXSubmissions;
    }

    /**
     * @param int $showAfterXSubmissions
     */
    public function setShowAfterXSubmissions($showAfterXSubmissions): void
    {
        $this->showAfterXSubmissions = $showAfterXSubmissions;
    }

    /**
     * Decide if the field should be displayed based on thr progressive profiling conditions.
     *
     * @param array|null $submissions
     */
    public function showForContact($submissions = null, ?Lead $lead = null, ?Form $form = null, ?DisplayManager $displayManager = null): bool
    {
        // Always show in the kiosk mode
        if (null !== $form && true === $form->getInKioskMode()) {
            return true;
        }

        // Hide the field if there is the submission count limit and hide it until the limit is overcame
        if (!$this->alwaysDisplay && $this->showAfterXSubmissions > 0 && null !== $submissions && $this->showAfterXSubmissions > count($submissions)) {
            return false;
        }

        if (!$this->alwaysDisplay && false === $this->showWhenValueExists) {
            // Hide the field if there is the value condition and if we already know the value for this field
            if ($submissions) {
                foreach ($submissions as $submission) {
                    if (!empty($submission[$this->alias]) && !$this->isAutoFill) {
                        return false;
                    }
                }
            }

            // Hide the field if the value is already known from the lead profile
            if (null !== $lead
                && $this->mappedField
                && 'contact' === $this->mappedObject
                && !empty($lead->getFieldValue($this->mappedField))
                && !$this->isAutoFill
            ) {
                return false;
            }
        }

        if ($displayManager && $displayManager->useProgressiveProfilingLimit()) {
            if (!$displayManager->showForField($this)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Was field displayed.
     *
     * @param array<string, mixed> $data
     */
    public function showForConditionalField(array $data): bool
    {
        if (!$parentField = $this->findParentFieldInForm()) {
            return true;
        }

        if (!isset($data[$parentField->getAlias() ?? ''])) {
            return false;
        }

        $sendValues = $data[$parentField->getAlias() ?? ''];
        if (!is_array($sendValues)) {
            $sendValues = [$sendValues];
        }

        foreach ($sendValues as $value) {
            // any value
            if ('' !== $value && !empty($this->conditions['any'])) {
                return true;
            }

            if ('notIn' === $this->conditions['expr']) {
                // value not matched
                if ('' !== $value && !in_array(InputHelper::clean($value), $this->conditions['values'])) {
                    return true;
                }
            } elseif (in_array(InputHelper::clean($value), $this->conditions['values'])) {
                return true;
            }
        }

        return false;
    }

    public function isCaptchaType(): bool
    {
        return 'captcha' === $this->type;
    }

    public function isFileType(): bool
    {
        return 'file' === $this->type;
    }

    public function hasChoices(): bool
    {
        return 'checkboxgrp' === $this->type
            || (array_key_exists('multiple', $this->properties) && 1 === $this->properties['multiple']);
    }

    /**
     * @return bool|null
     */
    public function isAlwaysDisplay()
    {
        return $this->alwaysDisplay;
    }

    /**
     * @param bool $alwaysDisplay
     */
    public function setAlwaysDisplay($alwaysDisplay): void
    {
        $this->alwaysDisplay = $alwaysDisplay;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public function setConditions($conditions): static
    {
        $this->isChanged('conditions', $conditions);
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @param string $parent
     */
    public function setParent($parent): static
    {
        $this->isChanged('parent', $parent);
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    private function findParentFieldInForm(): ?self
    {
        if (!$this->parent) {
            return null;
        }

        $fields = $this->form->getFields();
        foreach ($fields as $field) {
            if (intval($field->getId()) === intval($this->parent)) {
                return $field;
            }
        }

        return null;
    }

    public function getMappedObject(): ?string
    {
        return $this->mappedObject;
    }

    public function setMappedObject(?string $mappedObject): void
    {
        $this->mappedObject = $mappedObject;
        $this->resetLeadFieldIfValueIsEmpty($mappedObject);
    }

    public function getMappedField(): ?string
    {
        return $this->mappedField;
    }

    public function setMappedField(?string $mappedField): void
    {
        $this->mappedField = $mappedField;
        $this->resetLeadFieldIfValueIsEmpty($mappedField);
    }

    private function resetLeadFieldIfValueIsEmpty(?string $value): void
    {
        if ($value) {
            return;
        }

        /**
         * Ignoring this line because the leadField is deprecated and will be removed in Mautic 4.
         * Todo: Use mappedObject or mappedField instead.
         *
         * @phpstan-ignore-next-line
         */
        $this->leadField = null;
    }

    public function isAutoFillReadOnly(): bool
    {
        return $this->isAutoFill && $this->isReadOnly;
    }

    public function getFieldWidth(): string
    {
        return empty($this->fieldWidth) ? '100%' : $this->fieldWidth;
    }

    public function setFieldWidth(?string $fieldWidth): self
    {
        $this->isChanged('fieldWidth', $fieldWidth);
        $this->fieldWidth = $fieldWidth;

        return $this;
    }

    public function setIsReadOnly(?bool $isReadOnly): void
    {
        $this->isReadOnly = $isReadOnly ?? false;
    }

    public function getPermissionUser(): mixed
    {
        return $this->form?->getCreatedBy();
    }
}
