<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report;

/**
 * Class ObjectDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class ObjectDAO
{
    /**
     * @var int
     */
    private $object;

    /**
     * @var string
     */
    private $objectId;

    /**
     * @var FieldDAO[]
     */
    private $fields = [];

    /**
     * ObjectDAO constructor.
     * @param string       $object
     * @param int    $objectId
     */
    public function __construct($object, $objectId)
    {
        $this->object = $object;
        $this->objectId = $objectId;
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
     * @return FieldDAO[]
     */
    public function getFields()
    {
        return $this->fields;
    }
}
