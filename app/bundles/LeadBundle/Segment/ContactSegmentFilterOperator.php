<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;

class ContactSegmentFilterOperator
{
    /**
     * @var FilterOperatorProviderInterface
     */
    private $filterOperatorProvider;

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
