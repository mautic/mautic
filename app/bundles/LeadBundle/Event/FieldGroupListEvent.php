<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fired when the field group list is built for a view.
 * Listeners may add or modify groups via setGroups().
 */
final class FieldGroupListEvent extends Event
{
    /**
     * @param array<string, string> $groups [alias => displayName]
     * @param string                $object 'lead' | 'company'
     */
    public function __construct(
        private array $groups,
        private readonly string $object = 'lead',
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param array<string, string> $groups
     */
    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function getObject(): string
    {
        return $this->object;
    }
}
