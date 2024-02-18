<?php

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;

class ContactSegmentFilterOperator
{
    public function __construct(
        private FilterOperatorProviderInterface $filterOperatorProvider
    ) {
    }

    /**
     * @param string $operator
     *
     * @return string
     */
    public function fixOperator($operator)
    {
        $options = $this->filterOperatorProvider->getAllOperators();

        if (empty($options[$operator])) {
            return $operator;
        }

        $operatorDetails = $options[$operator];

        return $operatorDetails['expr'];
    }
}
