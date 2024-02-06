<?php

namespace Mautic\LeadBundle\Entity;

/**
 * Interface CustomFieldEntityInterface.
 */
interface CustomFieldEntityInterface
{
    /**
     * Set fields.
     *
     * @return mixed
     */
    public function setFields($fields);

    /**
     * Get fields.
     *
     * @return mixed
     */
    public function getFields($fields);

    /**
     * Update field value.
     *
     * @param string $oldValue
     *
     * @return mixed
     */
    public function addUpdatedField($alias, $value, $oldValue = '');

    /**
     * Get updated fields.
     *
     * @return mixed
     */
    public function getUpdatedFields();

    /**
     * Get a field value (should include those in updated fields).
     *
     * @return mixed
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
     *
     * @return mixed
     */
    public function getProfileFields();
}
