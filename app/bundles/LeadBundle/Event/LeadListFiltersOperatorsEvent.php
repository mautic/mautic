<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

final class LeadListFiltersOperatorsEvent extends CommonEvent
{
    /**
     * @var array<string, mixed[]>
     */
    private array $operators = [];

    /**
     * @return array<string, mixed[]>
     */
    public function getOperators(): array
    {
        return $this->operators;
    }

    /**
     * Add a new operator for list filters
     * Please refer to OperatorListTrait.php, inside getFilterExpressionFunctions method, for examples of operators.
     *
     * @see OperatorListTrait
     *
     * @param string $operatorKey
     * @param array  $operatorConfig
     */
    public function addOperator($operatorKey, $operatorConfig): void
    {
        if (!array_key_exists($operatorKey, $this->operators)) {
            $this->operators[$operatorKey] = $operatorConfig;
        }
    }
}
