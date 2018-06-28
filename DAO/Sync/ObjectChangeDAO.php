<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync;

/**
 * Class ObjectChangeDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class ObjectChangeDAO
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var mixed[] name => value
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
     * @param int       $id
     * @param string    $entity
     * @param int|null  $changeTimestamp
     */
    public function __construct($id, $entity, $changeTimestamp = null)
    {
        $this->id = $id;
        $this->entity = $entity;
        $this->changeTimestamp = $changeTimestamp;
    }

    /**
     * @param string    $name
     * @param mixed     $value
     *
     * @return self
     */
    public function addField($name, $value)
    {
        $this->fields[$name] = $value;

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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            return null;
        }
        return $this->fields[$name];
    }

    /**
     * @return mixed[string] name => value
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
