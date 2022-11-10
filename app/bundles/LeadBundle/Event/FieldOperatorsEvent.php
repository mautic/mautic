<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that collects operators for a specific field.
 */
final class FieldOperatorsEvent extends Event
{
    private string $type;

    private string $field;

    /**
     * @var array<string,string>
     */
    private array $operators;

    /**
     * @var array<string,array<string,string>>
     */
    private array $allOperators;

    /**
     * $allOperators example:
     * [
     *      '=' => [
     *          'label' => 'equals',
     *          'expr' => 'eq',
     *          'negate_expr' => 'neq',
     *      ],
     *      '!=' => [
     *          'label' => 'not equal',
     *          'expr' => 'neq',
     *          'negate_expr' => 'eq',
     *      ],
     *  ];.
     *
     * $defaultOperators example:
     * [
     *      'equals' => '=',
     *      'not equal' => '!='
     * ]
     *
     * @param array<string,string>               $defaultOperators
     * @param array<string,array<string,string>> $allOperators
     */
    public function __construct(string $type, string $field, array $allOperators, array $defaultOperators)
    {
        $this->type         = $type;
        $this->field        = $field;
        $this->allOperators = $allOperators;
        $this->operators    = $defaultOperators;
    }

    /**
     * @return array<string,string>
     */
    public function getOperators(): array
    {
        return $this->operators;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function addOperator(string $operator): void
    {
        if (isset($this->allOperators[$operator])) {
            $this->operators[$this->allOperators[$operator]['label']] = $operator;
        }
    }
}
