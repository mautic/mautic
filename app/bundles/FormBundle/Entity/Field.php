<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class Field.
 */
class Field
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
     * @var bool
     */
    private $showLabel = true;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $isCustom = false;

    /**
     * @var array
     */
    private $customParameters = [];

    /**
     * @var string
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $isRequired = false;

    /**
     * @var string
     */
    private $validationMessage;

    /**
     * @var string
     */
    private $helpMessage;

    /**
     * @var int
     */
    private $order = 0;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var array
     */
    private $validation = [];

    /**
     * @var Form
     */
    private $form;

    /**
     * @var string
     */
    private $labelAttributes;

    /**
     * @var string
     */
    private $inputAttributes;

    /**
     * @var string
     */
    private $containerAttributes;

    /**
     * @var string
     */
    private $leadField;

    /**
     * @var bool
     */
    private $saveResult = true;

    /**
     * @var bool
     */
    private $isAutoFill = false;

    /**
     * @var array
     */
    private $changes;

    /**
     * @var
     */
    private $sessionId;

    /**
     * @var bool
     */
    private $showWhenValueExists;

    /**
     * @var int
     */
    private $showAfterXSubmissions;

    /**
     * Reset properties on clone.
     */
    public function __clone()
    {
        $this->id   = null;
        $this->form = null;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('form_fields')
            ->setCustomRepositoryClass('Mautic\FormBundle\Entity\FieldRepository')
            ->addIndex(['type'], 'form_field_type_search');

        $builder->addId();

        $builder->addField('label', 'text');

        $builder->createField('showLabel', 'boolean')
            ->columnName('show_label')
            ->nullable()
            ->build();

        $builder->addField('alias', 'string');

        $builder->addField('type', 'string');

        $builder->createField('isCustom', 'boolean')
            ->columnName('is_custom')
            ->build();

        $builder->createField('customParameters', 'array')
            ->columnName('custom_parameters')
            ->nullable()
            ->build();

        $builder->createField('defaultValue', 'text')
            ->columnName('default_value')
            ->nullable()
            ->build();

        $builder->createField('isRequired', 'boolean')
            ->columnName('is_required')
            ->build();

        $builder->createField('validationMessage', 'text')
            ->columnName('validation_message')
            ->nullable()
            ->build();

        $builder->createField('helpMessage', 'text')
            ->columnName('help_message')
            ->nullable()
            ->build();

        $builder->createField('order', 'integer')
            ->columnName('field_order')
            ->nullable()
            ->build();

        $builder->createField('properties', 'array')
            ->nullable()
            ->build();

        $builder->createField('validation', 'json_array')
            ->nullable()
            ->build();

        $builder->createManyToOne('form', 'Form')
            ->inversedBy('fields')
            ->addJoinColumn('form_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addNullableField('labelAttributes', 'string', 'label_attr');

        $builder->addNullableField('inputAttributes', 'string', 'input_attr');

        $builder->addNullableField('containerAttributes', 'string', 'container_attr');

        $builder->addNullableField('leadField', 'string', 'lead_field');

        $builder->addNullableField('saveResult', 'boolean', 'save_result');

        $builder->addNullableField('isAutoFill', 'boolean', 'is_auto_fill');

        $builder->addNullableField('showWhenValueExists', 'boolean', 'show_when_value_exists');

        $builder->addNullableField('showAfterXSubmissions', 'integer', 'show_after_x_submissions');
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
                    'labelAttributes',
                    'inputAttributes',
                    'containerAttributes',
                    'leadField',
                    'saveResult',
                    'isAutoFill',
                ]
            )
            ->build();
    }

    /**
     * @param $prop
     * @param $val
     */
    private function isChanged($prop, $val)
    {
        if ($this->$prop != $val) {
            $this->changes[$prop] = [$this->$prop, $val];
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
     * @return Field
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
     * Set alias.
     *
     * @param string $alias
     *
     * @return Field
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
     * Set type.
     *
     * @param string $type
     *
     * @return Field
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
     * @return Field
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
     * @return Field
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
     * Proxy function to getIsRequired.
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->getIsRequired();
    }

    /**
     * Set order.
     *
     * @param int $order
     *
     * @return Field
     */
    public function setOrder($order)
    {
        $this->isChanged('order', $order);
        $this->order = $order;

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
     * Set properties.
     *
     * @param array $properties
     *
     * @return Field
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
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set validation.
     *
     * @param array $validation
     *
     * @return Field
     */
    public function setValidation($validation)
    {
        $this->isChanged('validation', $validation);
        $this->validation = $validation;

        return $this;
    }

    /**
     * Get validation.
     *
     * @return array
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * Set validationMessage.
     *
     * @param string $validationMessage
     *
     * @return Field
     */
    public function setValidationMessage($validationMessage)
    {
        $this->isChanged('validationMessage', $validationMessage);
        $this->validationMessage = $validationMessage;

        return $this;
    }

    /**
     * Get validationMessage.
     *
     * @return string
     */
    public function getValidationMessage()
    {
        return $this->validationMessage;
    }

    /**
     * Set form.
     *
     * @param Form $form
     *
     * @return Field
     */
    public function setForm(Form $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Get form.
     *
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Set labelAttributes.
     *
     * @param string $labelAttributes
     *
     * @return Field
     */
    public function setLabelAttributes($labelAttributes)
    {
        $this->isChanged('labelAttributes', $labelAttributes);
        $this->labelAttributes = $labelAttributes;

        return $this;
    }

    /**
     * Get labelAttributes.
     *
     * @return string
     */
    public function getLabelAttributes()
    {
        return $this->labelAttributes;
    }

    /**
     * Set inputAttributes.
     *
     * @param string $inputAttributes
     *
     * @return Field
     */
    public function setInputAttributes($inputAttributes)
    {
        $this->isChanged('inputAttributes', $inputAttributes);
        $this->inputAttributes = $inputAttributes;

        return $this;
    }

    /**
     * Get inputAttributes.
     *
     * @return string
     */
    public function getInputAttributes()
    {
        return $this->inputAttributes;
    }

    /**
     * @return mixed
     */
    public function getContainerAttributes()
    {
        return $this->containerAttributes;
    }

    /**
     * @param $containerAttributes
     *
     * @return $this
     */
    public function setContainerAttributes($containerAttributes)
    {
        $this->containerAttributes = $containerAttributes;

        return $this;
    }

    /**
     * @return array
     */
    public function convertToArray()
    {
        return get_object_vars($this);
    }

    /**
     * Set showLabel.
     *
     * @param bool $showLabel
     *
     * @return Field
     */
    public function setShowLabel($showLabel)
    {
        $this->isChanged('showLabel', $showLabel);
        $this->showLabel = $showLabel;

        return $this;
    }

    /**
     * Get showLabel.
     *
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
        return $this->getShowLabel();
    }

    /**
     * Set helpMessage.
     *
     * @param string $helpMessage
     *
     * @return Field
     */
    public function setHelpMessage($helpMessage)
    {
        $this->isChanged('helpMessage', $helpMessage);
        $this->helpMessage = $helpMessage;

        return $this;
    }

    /**
     * Get helpMessage.
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return $this->helpMessage;
    }

    /**
     * Set isCustom.
     *
     * @param bool $isCustom
     *
     * @return Field
     */
    public function setIsCustom($isCustom)
    {
        $this->isCustom = $isCustom;

        return $this;
    }

    /**
     * Get isCustom.
     *
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
        return $this->getIsCustom();
    }

    /**
     * Set customParameters.
     *
     * @param array $customParameters
     *
     * @return Field
     */
    public function setCustomParameters($customParameters)
    {
        $this->customParameters = $customParameters;

        return $this;
    }

    /**
     * Get customParameters.
     *
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
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return mixed
     */
    public function getLeadField()
    {
        return $this->leadField;
    }

    /**
     * @param mixed $leadField
     */
    public function setLeadField($leadField)
    {
        $this->leadField = $leadField;
    }

    /**
     * @return mixed
     */
    public function getSaveResult()
    {
        return $this->saveResult;
    }

    /**
     * @param mixed $saveResult
     */
    public function setSaveResult($saveResult)
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
    public function setIsAutoFill($isAutoFill)
    {
        $this->isAutoFill = $isAutoFill;
    }

    /**
     * @return bool
     */
    public function getShowWhenValueExists()
    {
        return $this->showWhenValueExists;
    }

    /**
     * @param bool $showWhenValueExists
     */
    public function setShowWhenValueExists($showWhenValueExists)
    {
        $this->showWhenValueExists = $showWhenValueExists;
    }

    /**
     * @return int
     */
    public function getShowAfterXSubmissions()
    {
        return $this->showAfterXSubmissions;
    }

    /**
     * @param int $showAfterXSubmissions
     */
    public function setShowAfterXSubmissions($showAfterXSubmissions)
    {
        $this->showAfterXSubmissions = $showAfterXSubmissions;
    }

    /**
     * Decide if the field should be displayed based on thr progressive profiling conditions.
     *
     * @param array|null $submissions
     * @param Lead       $lead
     * @param Form       $form
     *
     * @return bool
     */
    public function showForContact($submissions = null, Lead $lead = null, Form $form = null)
    {
        // Always show in the kiosk mode
        if ($form !== null && $form->getInKioskMode() === true) {
            return true;
        }

        // Hide the field if there is the submission count limit and hide it until the limit is overcame
        if ($this->showAfterXSubmissions > 0 && $this->showAfterXSubmissions > count($submissions)) {
            return false;
        }

        if ($this->showWhenValueExists === false) {
            // Hide the field if there is the value condition and if we already know the value for this field
            if ($submissions) {
                foreach ($submissions as $submission) {
                    if (!empty($submission[$this->alias]) && !$this->isAutoFill) {
                        return false;
                    }
                }
            }

            // Hide the field if the value is already known from the lead profile
            if ($lead !== null && $this->leadField && !empty($lead->getFieldValue($this->leadField)) && !$this->isAutoFill) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isCaptchaType()
    {
        return $this->type === 'captcha';
    }

    /**
     * @return bool
     */
    public function isFileType()
    {
        return $this->type === 'file';
    }
}
