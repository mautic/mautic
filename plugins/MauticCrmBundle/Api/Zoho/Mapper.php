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

use MauticPlugin\MauticCrmBundle\Api\Zoho\Xml\Writer;

class Mapper
{
    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var array
     */
    protected $contact = [];

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $mappedFields = [];

    /**
     * @var
     */
    protected $object;

    /**
     * Mapper constructor.
     *
     * @param       $object
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
        $this->writer = new Writer($object);
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
     * @param $id
     *
     * @return int If any single field is mapped, return 1 to count as one contact to be updated
     */
    public function map($id, $zohoId = null)
    {
        $mapped = 0;
        $row    = $this->writer->row($id);

        if ($zohoId) {
            $row->add('Id', $zohoId);
        }

        foreach ($this->mappedFields as $zohoField => $mauticField) {
            $field = $this->getField($zohoField);
            if ($field && isset($this->contact[$mauticField]) && $this->contact[$mauticField]) {
                $mapped   = 1;
                $apiField = $field['dv'];
                $apiValue = $this->contact[$mauticField];

                $row->add($apiField, $apiValue);
            }
        }

        return $mapped;
    }

    /**
     * @return string
     */
    public function getXml()
    {
        return $this->writer->write();
    }

    /**
     * @param $fieldName
     *
     * @return mixed
     */
    protected function getField($fieldName)
    {
        return isset($this->fields[$this->object][$fieldName]) ?
            $this->fields[$this->object][$fieldName] :
            null;
    }
}
