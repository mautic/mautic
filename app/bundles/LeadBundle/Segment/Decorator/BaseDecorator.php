<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Entity\RegexTrait;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;
use Mautic\LeadBundle\Segment\LeadSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;

class BaseDecorator implements FilterDecoratorInterface
{
    use RegexTrait;

    /**
     * @var LeadSegmentFilterOperator
     */
    protected $leadSegmentFilterOperator;

    public function __construct(
        LeadSegmentFilterOperator $leadSegmentFilterOperator
    ) {
        $this->leadSegmentFilterOperator = $leadSegmentFilterOperator;
    }

    public function getField(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $leadSegmentFilterCrate->getField();
    }

    public function getTable(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        if ($leadSegmentFilterCrate->isLeadType()) {
            return MAUTIC_TABLE_PREFIX.'leads';
        }

        return MAUTIC_TABLE_PREFIX.'companies';
    }

    public function getOperator(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $operator = $this->leadSegmentFilterOperator->fixOperator($leadSegmentFilterCrate->getOperator());

        switch ($operator) {
            case 'startsWith':
            case 'endsWith':
            case 'contains':
                return 'like';
                break;
        }

        return $operator;
    }

    public function getQueryType(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return BaseFilterQueryBuilder::getServiceId();
    }

    public function getParameterHolder(LeadSegmentFilterCrate $leadSegmentFilterCrate, $argument)
    {
        if (is_array($argument)) {
            $result = [];
            foreach ($argument as $arg) {
                $result[] = $this->getParameterHolder($leadSegmentFilterCrate, $arg);
            }

            return $result;
        }

        return ':'.$argument;
    }

    public function getParameterValue(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $filter = $leadSegmentFilterCrate->getFilter();

        switch ($leadSegmentFilterCrate->getType()) {
            case 'number':
                return (float) $filter;
            case 'boolean':
                return (bool) $filter;
        }

        switch ($this->getOperator($leadSegmentFilterCrate)) {
            case 'like':
            case 'notLike':
                return strpos($filter, '%') === false ? '%'.$filter.'%' : $filter;
            case 'contains':
                return '%'.$filter.'%';
            case 'startsWith':
                return $filter.'%';
            case 'endsWith':
                return '%'.$filter;
            case 'regexp':
            case 'notRegexp':
                return $this->prepareRegex($filter);
        }

        return $filter;
    }

    public function getAggregateFunc(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return false;
    }

    public function getWhere(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return null;
    }
}
