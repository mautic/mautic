<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\DAO\Sync\Order;

/**
 * Class ObjectChangeDAO
 */
class ObjectChangeDAO
{
    /**
     * @var string
     */
    private $object;

    /**
     * @var mixed
     */
    private $objectId;

    /**
     * @var string
     */
    private $mappedObject;

    /**
     * @var mixed
     */
    private $mappedId;

    /**
     * @var FieldDAO[]
     */
    private $fields = [];

    /**
     * ObjectChangeDAO constructor.
     *
     * @param string $object
     * @param mixed  $objectId
     * @param mixed  $mappedId     ID of the source object
     * @param string $mappedObject Name of the source object type
     */
    public function __construct($object, $objectId, $mappedObject, $mappedId)
    {
        $this->object       = $object;
        $this->objectId     = $objectId;
        $this->mappedId     = $mappedId;
        $this->mappedObject = $mappedObject;
    }

    /**
     * @param FieldDAO $fieldDAO
     *
     * @return $this
     */
    public function addField(FieldDAO $fieldDAO): ObjectChangeDAO
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
     * @param mixed $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Returns the name/type for the object in this system that is being synced to the other
     *
     * @return string
     */
    public function getMappedObject()
    {
        return $this->mappedObject;
    }

    /**
     * Returns the ID for the object in this system that is being synced to the other
     *
     * @return mixed|null
     */
    public function getMappedId()
    {
        return $this->mappedId;
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
    public function getFields(): array
    {
        return $this->fields;
    }
}
