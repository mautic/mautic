<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

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
     * @param $name
     *
     * @return bool
     */
    public function __get($name)
    {
        return $this->getFieldValue(strtolower($name));
    }

    /**
     * @param $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
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
            foreach ($this->fields as $group => $fields) {
                $return += $fields;
            }

            return $return;
        }

        return $this->fields;
    }

    /**
     * Add an updated field to persist to the DB and to note changes.
     *
     * @param        $alias
     * @param        $value
     * @param string $oldValue
     */
    public function addUpdatedField($alias, $value, $oldValue = '')
    {
        if (method_exists($this, 'isAnonymous') && $this->wasAnonymous == null) {
            $this->wasAnonymous = $this->isAnonymous();
        }

        $value = trim($value);
        if ($value == '') {
            // Ensure value is null for consistency
            $value = null;
        }

        $this->addChange('fields', [$alias => [$oldValue, $value]]);
        $this->updatedFields[$alias] = $value;
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
     * Get company field value.
     *
     * @param      $field
     * @param null $group
     *
     * @return bool
     */
    public function getFieldValue($field, $group = null)
    {
        if (isset($this->updatedFields[$field])) {
            return $this->updatedFields[$field];
        }

        if (!empty($group) && isset($this->fields[$group][$field])) {
            return $this->fields[$group][$field]['value'];
        }

        foreach ($this->fields as $group => $groupFields) {
            foreach ($groupFields as $name => $details) {
                if ($name == $field) {
                    return $details['value'];
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
            if (isset($this->fields['core'])) {
                foreach ($this->fields as $group => $fields) {
                    foreach ($fields as $alias => $field) {
                        $fieldValues[$alias] = $field['value'];
                    }
                }
            }

            return array_merge($fieldValues, $this->updatedFields);
        } else {
            // The fields are already flattened

            return $this->fields;
        }
    }
}
