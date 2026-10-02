<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

interface CustomFieldEntityInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param mixed[] $fields
     *
     * @return mixed
     */
    public function setFields(array $fields);

    /**
     * @return mixed
     */
    public function getFields();

    /**
     * @param string $alias
     * @param mixed  $value
     * @param string $oldValue
     *
     * @return mixed
     */
    public function addUpdatedField($alias, $value, $oldValue = '');

    /**
     * @return mixed
     */
    public function getUpdatedFields();

    /**
     * Get a field value (should include those in updated fields).
     *
     * @param string      $field alias
     * @param string|null $group
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
