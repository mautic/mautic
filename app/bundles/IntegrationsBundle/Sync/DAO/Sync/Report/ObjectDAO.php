<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report;

use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;

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
     * @var \DateTimeInterface
     */
    private $changeDateTime = null;

    /**
     * @param string                  $object
     * @param mixed                   $objectId
     * @param \DateTimeInterface|null $changeDateTime
     */
    public function __construct($object, $objectId, ?\DateTimeInterface $changeDateTime = null)
    {
        $this->object         = $object;
        $this->objectId       = $objectId;
        $this->changeDateTime = $changeDateTime;
    }

    /**
     * @return null|\DateTimeInterface
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
    public function setChangeDateTime(\DateTimeInterface $changeDateTime): self
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
     *
     * @throws FieldNotFoundException
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            throw new FieldNotFoundException($name, $this->object);
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
