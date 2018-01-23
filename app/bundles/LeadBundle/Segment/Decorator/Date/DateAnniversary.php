<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date;

use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;

class DateAnniversary implements DateOptionsInterface, FilterDecoratorInterface
{
    /**
     * @var DateDecorator
     */
    private $dateDecorator;

    public function __construct(DateDecorator $dateDecorator)
    {
        $this->dateDecorator = $dateDecorator;
    }

    /**
     * @return string
     */
    public function getDateValue()
    {
        return '%'.date('-m-d');
    }

    public function getField(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->dateDecorator->getField($leadSegmentFilterCrate);
    }

    public function getTable(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->dateDecorator->getTable($leadSegmentFilterCrate);
    }

    public function getOperator(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return 'like';
    }

    public function getParameterHolder(LeadSegmentFilterCrate $leadSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($leadSegmentFilterCrate, $argument);
    }

    public function getParameterValue(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->getDateValue();
    }

    public function getQueryType(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->dateDecorator->getQueryType($leadSegmentFilterCrate);
    }

    public function getAggregateFunc(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->dateDecorator->getAggregateFunc($leadSegmentFilterCrate);
    }
}
