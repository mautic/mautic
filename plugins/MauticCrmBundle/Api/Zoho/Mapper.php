<?php

namespace MauticPlugin\MauticCrmBundle\Api\Zoho;

use MauticPlugin\MauticCrmBundle\Api\Zoho\Exception\MatchingKeyNotFoundException;

class Mapper
{
    private array $contact = [];

    private array $mappedFields = [];

    private $object;

    /**
     * @var array[]
     */
    private array $objectMappedValues = [];

    /**
     * Used to keep track of the key used to map contact ID with the response Zoho returns.
     */
    private int $objectCounter = 0;

    /**
     * Used to map contact ID with the response Zoho returns.
     */
    private array $contactMapper = [];

    public function __construct(
        private array $fields,
    ) {
    }

    /**
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return $this
     */
    public function setContact(array $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return $this
     */
    public function setMappedFields(array $fields)
    {
        $this->mappedFields = $fields;

        return $this;
    }

    /**
     * @param int      $mauticContactId Mautic Contact ID
     * @param int|null $zohoId          Zoho ID if known
     *
     * @return int If any single field is mapped, return 1 to count as one contact to be updated
     */
    public function map($mauticContactId, $zohoId = null): int
    {
        $mapped             = 0;
        $objectMappedValues = [];

        foreach ($this->mappedFields as $zohoField => $mauticField) {
            $field = $this->getField($zohoField);
            if ($field && isset($this->contact[$mauticField]) && $this->contact[$mauticField]) {
                $mapped   = 1;
                $apiField = $field['api_name'];
                $apiValue = $this->contact[$mauticField];

                $objectMappedValues[$apiField] = $apiValue;
            }

            if ($zohoId) {
                $objectMappedValues['id'] = $zohoId;
            }
        }

        $this->objectMappedValues[$this->objectCounter] = $objectMappedValues;
        $this->contactMapper[$this->objectCounter]      = $mauticContactId;

        ++$this->objectCounter;

        return $mapped;
    }

    /**
     * @return array
     */
    public function getArray()
    {
        return $this->objectMappedValues;
    }

    /**
     * @param int $key
     *
     * @return int
     *
     * @throws MatchingKeyNotFoundException
     */
    public function getContactIdByKey($key)
    {
        if (isset($this->contactMapper[$key])) {
            return $this->contactMapper[$key];
        }

        throw new MatchingKeyNotFoundException();
    }

    /**
     * @return mixed
     */
    private function getField($fieldName)
    {
        return $this->fields[$this->object][$fieldName] ?? null;
    }
}
