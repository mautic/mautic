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

    public function getField(string $key, ?string $group = null): array|false;

    /**
     * Get flat array of profile fields without groups.
     *
     * @return mixed
     */
    public function getProfileFields();
}
