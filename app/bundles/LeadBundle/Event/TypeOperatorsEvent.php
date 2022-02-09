<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that collects operators for different field types.
 */
final class TypeOperatorsEvent extends Event
{
    /**
     * @var array<string,array<string,string>>
     */
    private array $operators = [];

    /**
     * $operators example:
     * [
     *      'include' => ['=' => 'like'],
     *      'exclude' => ['!=' => '!like'],
     * ].
     */
    public function setOperatorsForFieldType(string $fieldType, array $operators): void
    {
        $this->operators[$fieldType] = $operators;
    }

    public function getOperatorsForAllFieldTypes(): array
    {
        return $this->operators;
    }
}
