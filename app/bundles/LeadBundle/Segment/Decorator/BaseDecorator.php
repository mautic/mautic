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

use Mautic\LeadBundle\Segment\FilterQueryBuilder\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;
use Mautic\LeadBundle\Segment\LeadSegmentFilterOperator;
use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;

class BaseDecorator implements FilterDecoratorInterface
{
    /**
     * @var LeadSegmentFilterOperator
     */
    private $leadSegmentFilterOperator;

    /**
     * @var LeadSegmentFilterDescriptor
     */
    private $leadSegmentFilterDescriptor;

    public function __construct(
        LeadSegmentFilterOperator $leadSegmentFilterOperator,
        LeadSegmentFilterDescriptor $leadSegmentFilterDescriptor
    ) {
        $this->leadSegmentFilterOperator   = $leadSegmentFilterOperator;
        $this->leadSegmentFilterDescriptor = $leadSegmentFilterDescriptor;
    }

    public function getField(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (empty($this->leadSegmentFilterDescriptor[$originalField])) {
            return $originalField;
        }

        return $this->leadSegmentFilterDescriptor[$originalField]['field'];
    }

    public function getTable(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (empty($this->leadSegmentFilterDescriptor[$originalField])) {
            if ($leadSegmentFilterCrate->isLeadType()) {
                return 'leads';
            }

            return 'companies';
        }

        return $this->leadSegmentFilterDescriptor[$originalField]['foreign_table'];
    }

    public function getOperator(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->leadSegmentFilterOperator->fixOperator($leadSegmentFilterCrate->getOperator());
    }

    public function getQueryType(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (!isset($this->leadSegmentFilterDescriptor[$originalField]['type'])) {
            return BaseFilterQueryBuilder::getServiceId();
        }

        return $this->leadSegmentFilterDescriptor[$originalField]['type'];
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
            case 'contains':
                return '%'.$filter.'%';
            case 'startsWith':
                return $filter.'%';
            case 'endsWith':
                return '%'.$filter;
        }

        return $filter;
    }

    public function getAggregateFunc(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        return isset($this->leadSegmentFilterDescriptor[$originalField]['func']) ?
            $this->leadSegmentFilterDescriptor[$originalField]['func'] : false;
    }
}
