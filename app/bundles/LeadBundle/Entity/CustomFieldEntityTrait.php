<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Field\SchemaDefinition;
use Mautic\LeadBundle\Helper\CustomFieldHelper;
use Mautic\LeadBundle\Helper\CustomFieldValueHelper;

trait CustomFieldEntityTrait
{
    /**
     * Used by Mautic to populate the fields pulled from the DB.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Just a place to store updated field values so we don't have to loop through them again comparing.
     *
     * @var array
     */
    protected $updatedFields = [];

    /**
     * A place events can use to pass data around on the object to prevent issues like creating a contact and having it processed to be sent back
     * to the origin of creation in a webhook.
     *
     * @var array
     */
    protected $eventData = [];

    /**
     * @return bool
     */
    public function __get($name)
    {
        return $this->getFieldValue(strtolower($name));
    }

    /**
     * @return $this
     */
    public function __set($name, $value)
    {
        return $this->addUpdatedField(strtolower($name), $value);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $isSetter = str_starts_with($name, 'set');
        $isGetter = str_starts_with($name, 'get');

        if (($isSetter && array_key_exists(0, $arguments)) || $isGetter) {
            $fieldRequested = mb_strtolower(mb_substr($name, 3));
            $fields         = $this->getProfileFields();

            if (array_key_exists($fieldRequested, $fields)) {
                return ($isSetter) ? $this->addUpdatedField($fieldRequested, $arguments[0]) : $this->getFieldValue($fieldRequested);
            }
        }

        return parent::__call($name, $arguments);
    }

    public function setFields($fields): void
    {
        $this->fields = CustomFieldValueHelper::normalizeValues($fields);
    }

    /**
     * @param bool $ungroup
     *
     * @return array
     */
    public function getFields($ungroup = false)
    {
        if ($ungroup && isset($this->fields['core'])) {
            $return = [];
            foreach ($this->fields as $fields) {
                $return += $fields;
            }

            return $return;
        }

        return $this->fields;
    }

    /**
     * Add an updated field to persist to the DB and to note changes.
     *
     * @param string $oldValue
     *
     * @return $this
     */
    public function addUpdatedField($alias, $value, $oldValue = null)
    {
        // Don't allow overriding ID
        if ('id' === $alias) {
            return $this;
        }

        $property = (defined('self::FIELD_ALIAS')) ? str_replace(self::FIELD_ALIAS, '', $alias) : $alias;
        $field    = $this->getField($alias);
        $setter   = 'set'.ucfirst($property);

        if (null == $oldValue) {
            $oldValue = $this->getFieldValue($alias);
        } elseif ($field) {
            $oldValue = CustomFieldHelper::fixValueType($field['type'], $oldValue);
        }

        if (property_exists($this, $property) && method_exists($this, $setter)) {
            // Fixed custom field so use the setter but don't get caught in a loop such as a custom field called "notes"
            // Set empty value as null
            if ('' === $value) {
                $value = null;
            }
            $this->$setter($value);
        }

        if (is_string($value)) {
            $value = trim($value);
            if ('' === $value) {
                // Ensure value is null for consistency
                $value = null;

                if ('' === $oldValue) {
                    $oldValue = null;
                }
            }
        } elseif (is_array($value)) {
            // Flatten the array
            $value = implode('|', $value);
        }

        if ($field) {
            $value = CustomFieldHelper::fixValueType($field['type'], $value);
        }

        if ($oldValue !== $value && !(('' === $oldValue && null === $value) || (null === $oldValue && '' === $value))) {
            $this->addChange('fields', [$alias => [$oldValue, $value]]);
            $this->updatedFields[$alias] = $value;
        }

        return $this;
    }

    /**
     * Get the array of updated fields.
     *
     * @return array
     */
    public function getUpdatedFields()
    {
        return $this->updatedFields;
    }

    /**
     * @param string      $field
     * @param string|null $group
     *
     * @return mixed
     */
    public function getFieldValue($field, $group = null)
    {
        if (property_exists($this, $field)) {
            $value = $this->{'get'.ucfirst($field)}();

            if (null !== $value) {
                return $value;
            }
        }

        if (array_key_exists($field, $this->updatedFields)) {
            return $this->updatedFields[$field];
        }

        if ($field = $this->getField($field, $group)) {
            return CustomFieldHelper::fixValueType($field['type'], $field['value']);
        }

        return null;
    }

    /**
     * Get field details.
     *
     * @param string $key
     * @param string $group
     *
     * @return array|false
     */
    public function getField($key, $group = null)
    {
        if ($group && isset($this->fields[$group][$key])) {
            return $this->fields[$group][$key];
        }

        foreach ($this->fields as $groupFields) {
            foreach ($groupFields as $name => $details) {
                if ($name == $key) {
                    return $details;
                }
            }
        }

        return false;
    }

    /**
     * Get profile values.
     *
     * @return array
     */
    public function getProfileFields()
    {
        if (isset($this->fields['core'])) {
            $fieldValues = [
                'id' => $this->id,
            ];

            foreach ($this->fields as $group => $fields) {
                if ('all' === $group) {
                    continue;
                }

                foreach ($fields as $alias => $field) {
                    $fieldValues[$alias] = $field['value'];
                }
            }

            return array_merge($fieldValues, $this->updatedFields);
        } else {
            // The fields are already flattened

            return $this->fields;
        }
    }

    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    public function getEventData($key)
    {
        return $this->eventData[$key] ?? null;
    }

    /**
     * @return $this
     */
    public function setEventData($key, $value)
    {
        $this->eventData[$key] = $value;

        return $this;
    }

    protected static function loadFixedFieldMetadata(ClassMetadataBuilder $builder, array $fields, array $customFieldDefinitions)
    {
        foreach ($fields as $fieldProperty) {
            $field = (defined('self::FIELD_ALIAS')) ? self::FIELD_ALIAS.$fieldProperty : $fieldProperty;

            $type = 'text';
            if (isset($customFieldDefinitions[$field]) && !empty($customFieldDefinitions[$field]['type'])) {
                $type = $customFieldDefinitions[$field]['type'];
            }

            $builder->addNamedField(
                $fieldProperty,
                SchemaDefinition::getSchemaDefinition($field, $type, !empty($customFieldDefinitions[$field]['unique']))['type'],
                $field,
                true
            );
        }
    }
}
