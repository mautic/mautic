<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Report;

use Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException;

class ObjectDAO
{
    /**
     * @var FieldDAO[]
     */
    private array $fields = [];

    /**
     * @param string $object
     * @param mixed  $objectId
     */
    public function __construct(
        private $object,
        private $objectId,
        private ?\DateTimeInterface $changeDateTime = null,
    ) {
    }

    public function getChangeDateTime(): ?\DateTimeInterface
    {
        return $this->changeDateTime;
    }

    public function setChangeDateTime(\DateTimeInterface $changeDateTime): self
    {
        $this->changeDateTime = $changeDateTime;

        return $this;
    }

    /**
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
