<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Api\Zoho;

use MauticPlugin\MauticCrmBundle\Api\Zoho\Exception\MatchingKeyNotFoundException;

class Mapper
{
    /**
     * @var array
     */
    private $contact = [];

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $mappedFields = [];

    private $object;

    /**
     * @var array[]
     */
    private $objectMappedValues = [];

    /**
     * Used to keep track of the key used to map contact ID with the response Zoho returns.
     *
     * @var int
     */
    private $objectCounter = 0;

    /**
     * Used to map contact ID with the response Zoho returns.
     *
     * @var array
     */
    private $contactMapper = [];

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param $object
     *
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
    public function map($mauticContactId, $zohoId = null)
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
     * @param $fieldName
     *
     * @return mixed
     */
    private function getField($fieldName)
    {
        return isset($this->fields[$this->object][$fieldName])
            ?
            $this->fields[$this->object][$fieldName]
            :
            null;
    }
}
