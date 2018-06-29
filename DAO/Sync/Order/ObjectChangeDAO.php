<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order;

/**
 * Class ObjectChangeDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order
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
     * ObjectChangeDAO constructor.
     * @param string    $object
     * @param int       $objectId
     */
    public function __construct($object, $objectId)
    {
        $this->object = $object;
        $this->objectId = $objectId;
    }

    /**
     * @param FieldDAO $fieldDAO
     *
     * @return $this
     */
    public function addField(FieldDAO $fieldDAO)
    {
        $this->fields[$fieldDAO->getName()] = $fieldDAO;

        return $this;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
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
}
