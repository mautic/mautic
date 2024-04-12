<?php

namespace Mautic\LeadBundle\Entity;

interface CustomFieldEntityInterface
{
    /**
     * @param mixed[] $fields
     */
    public function setFields($fields);

    public function getFields();

    /**
     * @param string $alias
     * @param string $oldValue
     */
    public function addUpdatedField($alias, $value, $oldValue = '');

    public function getUpdatedFields();

    /**
     * Get a field value (should include those in updated fields).
     *
     * @param string      $field alias
     * @param string|null $group
     */
    public function getFieldValue($field, $group = null);

    /**
     * Get field details.
     *
     * @param string $key
     * @param string $group
     *
     * @return array|false
     */
    public function getField($key, $group = null);

    /**
     * Get flat array of profile fields without groups.
     */
    public function getProfileFields();
}
