<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that collects operators for different field types.
 */
final class TypeOperatorsEvent extends Event
{
    /**
     * @var array<string,mixed[]>
     */
    private array $operators = [];

    /**
     * $operators example:
     * [
     *      'include' => ['=' => 'like'],
     *      'exclude' => ['!=' => '!like'],
     * ].
     *
     * @param array<string,mixed[]> $operators
     */
    public function setOperatorsForFieldType(string $fieldType, array $operators): void
    {
        $this->operators[$fieldType] = $operators;
    }

    /**
     * @return array<string,mixed[]>
     */
    public function getOperatorsForAllFieldTypes(): array
    {
        return $this->operators;
    }
}
