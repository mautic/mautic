<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that collects choices for different list field types.
 */
final class ListFieldChoicesEvent extends Event
{
    /**
     * @var array<string,mixed[]>
     */
    private array $choicesForTypes = [];

    /**
     * @var array<string,mixed[]>
     */
    private array $choicesForAliases = [];

    private string $searchTerm = '';

    /**
     * @param mixed[] $choices
     */
    public function setChoicesForFieldType(string $fieldType, array $choices): void
    {
        $this->choicesForTypes[$fieldType] = $choices;
    }

    /**
     * @param mixed[] $choices
     */
    public function setChoicesForFieldAlias(string $fieldAlias, array $choices): void
    {
        $this->choicesForAliases[$fieldAlias] = $choices;
    }

    /**
     * @return array<string,mixed[]>
     */
    public function getChoicesForAllListFieldTypes(): array
    {
        return $this->choicesForTypes;
    }

    /**
     * @return array<string,mixed[]>
     */
    public function getChoicesForAllListFieldAliases(): array
    {
        return $this->choicesForAliases;
    }

    public function setSearchTerm(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }

    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }
}
