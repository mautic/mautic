<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

final class LeadListFiltersOperatorsEvent extends CommonEvent
{
    /**
     * @deprecated to be removed in Mautic 3
     *
     * @param array $operators @deprecated to be removed in Mautic 3. Subscribe operators instead.
     */
    public function __construct(
        private $operators,
    ) {
    }

    /**
     * @return array
     */
    public function getOperators()
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
