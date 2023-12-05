<?php

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;

class ContactSegmentFilterOperator
{
    private \Mautic\LeadBundle\Provider\FilterOperatorProviderInterface $filterOperatorProvider;

    public function __construct(
        FilterOperatorProviderInterface $filterOperatorProvider
    ) {
        $this->filterOperatorProvider = $filterOperatorProvider;
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
