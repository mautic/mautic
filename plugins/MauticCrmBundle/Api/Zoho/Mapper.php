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

    /**
     * @var
     */
    private $object;

    /**
     * @var array
     */
    private $objectMappedValues = [];

    /**
     * Mapper constructor.
     *
     * @param array $fields
     */
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
     * @param array $contact
     *
     * @return $this
     */
    public function setContact(array $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @param array $fields
     *
     * @return $this
     */
    public function setMappedFields(array $fields)
    {
        $this->mappedFields = $fields;

        return $this;
    }

    /**
     * @param int|null $id Zoho ID if known
     *
     * @return int If any single field is mapped, return 1 to count as one contact to be updated
     */
    public function map($id = null)
    {
        $mapped             = 0;
        $objectMappedValues = [];

        if ($id) {
            $objectMappedValues['id'] = $id;
        }

        foreach ($this->mappedFields as $zohoField => $mauticField) {
            $field = $this->getField($zohoField);
            if ($field && isset($this->contact[$mauticField]) && $this->contact[$mauticField]) {
                $mapped   = 1;
                $apiField = $field['api_name'];
                $apiValue = $this->contact[$mauticField];

                $objectMappedValues[$apiField] = $apiValue;
            }
        }

        $this->objectMappedValues = $objectMappedValues;

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
