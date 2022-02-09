<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that collects choices for different list field types.
 */
final class ListFieldChoicesEvent extends Event
{
    private array $choicesForTypes = [];

    private array $choicesForAliases = [];

    public function setChoicesForFieldType(string $fieldType, array $choices): void
    {
        $this->choicesForTypes[$fieldType] = $choices;
    }

    public function setChoicesForFieldAlias(string $fieldAlias, array $choices): void
    {
        $this->choicesForAliases[$fieldAlias] = $choices;
    }

    public function getChoicesForAllListFieldTypes(): array
    {
        return $this->choicesForTypes;
    }

    public function getChoicesForAllListFieldAliases(): array
    {
        return $this->choicesForAliases;
    }
}
