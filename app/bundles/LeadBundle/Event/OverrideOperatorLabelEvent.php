<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class OverrideOperatorLabelEvent extends Event
{
    /**
     * @param array<mixed> $typeOperatorsChoices
     */
    public function __construct(private array $typeOperatorsChoices, private string $fieldType)
    {
    }

    /**
     * @return mixed[]
     */
    public function getTypeOperatorsChoices(): array
    {
        return $this->typeOperatorsChoices;
    }

    /**
     * @param array<mixed> $typeOperatorsChoices
     */
    public function setTypeOperatorsChoices(array $typeOperatorsChoices): void
    {
        $this->typeOperatorsChoices = $typeOperatorsChoices;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }
}
