<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that collects operators for a specific field.
 */
class FieldOperatorsEvent extends Event
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $field;

    /**
     * @var array
     */
    private $operators;

    /**
     * @var aray
     */
    private $allOperators;

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
     */
    public function __construct(string $type, string $field, array $allOperators, array $defaultOperators)
    {
        $this->type         = $type;
        $this->field        = $field;
        $this->allOperators = $allOperators;
        $this->operators    = $defaultOperators;
    }

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
