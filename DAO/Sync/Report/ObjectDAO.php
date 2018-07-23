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
     * @var int|null
     */
    private $changeTimestamp = null;

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
     * @return int|null
     */
    public function getChangeTimestamp(): ?int
    {
        return $this->changeTimestamp;
    }

    /**
     * @param int|null $changeTimestamp
     *
     * @return ObjectDAO
     */
    public function setChangeTimestamp(?int $changeTimestamp): ObjectDAO
    {
        $this->changeTimestamp = $changeTimestamp;

        return $this;
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
     * @return FieldDAO|null
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
