<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report;

/**
 * Class ObjectChangeDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report
 */
class ObjectChangeDAO
{
    /**
     * @var string
     */
    private $object;

    /**
     * @var int
     */
    private $objectId;

    /**
     * @var FieldDAO[]
     */
    private $fields = [];

    /**
     * @var FieldChangeDAO[]
     */
    private $fieldsChanges = [];

    /**
     * @var int|null
     */
    private $changeTimestamp = null;

    /**
     * ObjectChangeDAO constructor.
     * @param string       $object
     * @param int    $objectId
     * @param int|null  $changeTimestamp
     */
    public function __construct($object, $objectId, $changeTimestamp = null)
    {
        $this->object = $object;
        $this->objectId = $objectId;
        $this->changeTimestamp = $changeTimestamp;
    }

    /**
     * @param FieldDAO $fieldDAO
     * @return $this
     */
    public function addField(FieldDAO $fieldDAO)
    {
        $this->fields[$fieldDAO->getName()] = $fieldDAO;

        return $this;
    }

    /**
     * @param FieldChangeDAO $fieldNameChange
     *
     * @return self
     */
    public function addFieldChange(FieldChangeDAO $fieldNameChange)
    {
        $this->fieldsChanges[] = $fieldNameChange;

        return $this;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $name
     *
     * @return FieldDAO
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            return null;
        }
        return $this->fields[$name];
    }

    /**
     * @return FieldDAO[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $name
     *
     * @return FieldChangeDAO
     */
    public function getFieldChange($name)
    {
        if (!isset($this->fieldsChanges[$name])) {
            return null;
        }
        return $this->fieldsChanges[$name];
    }

    /**
     * @return FieldChangeDAO[]
     */
    public function getFieldsChanges()
    {
        return $this->fieldsChanges;
    }

    /**
     * @return int|null
     */
    public function getChangeTimestamp()
    {
        return $this->changeTimestamp;
    }
}
