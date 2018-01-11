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

class BaseDecorator implements FilterDecoratorInterface
{
    /**
     * @var LeadSegmentFilterOperator
     */
    private $leadSegmentFilterOperator;

    public function __construct(LeadSegmentFilterOperator $leadSegmentFilterOperator)
    {
        $this->leadSegmentFilterOperator = $leadSegmentFilterOperator;
    }

    public function getField()
    {
    }

    public function getTable()
    {
    }

    public function getOperator(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->leadSegmentFilterOperator->fixOperator($leadSegmentFilterCrate->getOperator());
    }

    public function getParameterHolder($argument)
    {
    }

    public function getParameterValue()
    {
    }
}
