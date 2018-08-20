<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\DAO\Sync\Report;

/**
 * Class ObjectDAO
 */
class ObjectDAO
{
    /**
     * @var int
     */
    private $object;

    /**
     * @var mixed
     */
    private $objectId;

    /**
     * @var FieldDAO[]
     */
    private $fields = [];

    /**
     * @var \DateTimeInterface|null
     */
    private $changeDateTime = null;

    /**
     * ObjectDAO constructor.
     *
     * @param string                  $object
     * @param mixed                   $objectId
     * @param \DateTimeInterface|null $changeDateTime
     */
    public function __construct($object, $objectId, \DateTimeInterface $changeDateTime = null)
    {
        $this->object         = $object;
        $this->objectId       = $objectId;
        $this->changeDateTime = $changeDateTime;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getChangeDateTime(): ?\DateTimeInterface
    {
        return $this->changeDateTime;
    }

    /**
     * @param \DateTimeInterface $changeDateTime
     *
     * @return ObjectDAO
     */
    public function setChangeDateTime(\DateTimeInterface $changeDateTime): ObjectDAO
    {
        $this->changeDateTime = $changeDateTime;

        return $this;
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
     * @return mixed
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
