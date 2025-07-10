<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Report;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

class FieldDAO
{
    public const FIELD_CHANGED   = 'changed';

    public const FIELD_REQUIRED  = 'required';

    public const FIELD_UNCHANGED = 'unchanged';

    private ?\DateTimeInterface $changeDateTime = null;

    public function __construct(
        private string $name,
        private NormalizedValueDAO $value,
        private string $state = self::FIELD_CHANGED,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): NormalizedValueDAO
    {
        return $this->value;
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

    public function getState(): string
    {
        return $this->state;
    }
}
