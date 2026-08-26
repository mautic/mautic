<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Report;

use Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException;

final class ObjectDAO
{
    /**
     * @var FieldDAO[]
     */
    private array $fields = [];

    /**
     * @param mixed $objectId
     */
    public function __construct(
        private readonly string $object,
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

    public function addField(FieldDAO $fieldDAO): static
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

    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * @throws FieldNotFoundException
     */
    public function getField(string $name): FieldDAO
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
