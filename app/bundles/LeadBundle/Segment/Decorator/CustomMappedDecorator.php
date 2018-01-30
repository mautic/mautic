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

use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;
use Mautic\LeadBundle\Segment\LeadSegmentFilterOperator;
use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;

class CustomMappedDecorator extends BaseDecorator
{
    /**
     * @var LeadSegmentFilterDescriptor
     */
    protected $leadSegmentFilterDescriptor;

    public function __construct(
        LeadSegmentFilterOperator $leadSegmentFilterOperator,
        LeadSegmentFilterDescriptor $leadSegmentFilterDescriptor
    ) {
        parent::__construct($leadSegmentFilterOperator);
        $this->leadSegmentFilterDescriptor = $leadSegmentFilterDescriptor;
    }

    public function getField(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (empty($this->leadSegmentFilterDescriptor[$originalField]['field'])) {
            return parent::getField($leadSegmentFilterCrate);
        }

        return $this->leadSegmentFilterDescriptor[$originalField]['field'];
    }

    public function getTable(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (empty($this->leadSegmentFilterDescriptor[$originalField]['foreign_table'])) {
            return parent::getTable($leadSegmentFilterCrate);
        }

        return MAUTIC_TABLE_PREFIX.$this->leadSegmentFilterDescriptor[$originalField]['foreign_table'];
    }

    public function getQueryType(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (!isset($this->leadSegmentFilterDescriptor[$originalField]['type'])) {
            return parent::getQueryType($leadSegmentFilterCrate);
        }

        return $this->leadSegmentFilterDescriptor[$originalField]['type'];
    }

    public function getAggregateFunc(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        return isset($this->leadSegmentFilterDescriptor[$originalField]['func']) ?
            $this->leadSegmentFilterDescriptor[$originalField]['func'] : false;
    }
}
