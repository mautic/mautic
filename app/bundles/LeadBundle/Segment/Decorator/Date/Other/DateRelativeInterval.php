<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date\Other;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

class DateRelativeInterval implements FilterDecoratorInterface
{
    /**
     * @var DateDecorator
     */
    private $dateDecorator;

    /**
     * @var string
     */
    private $originalValue;

    /**
     * @param DateDecorator $dateDecorator
     * @param string        $originalValue
     */
    public function __construct(DateDecorator $dateDecorator, $originalValue)
    {
        $this->dateDecorator = $dateDecorator;
        $this->originalValue = $originalValue;
    }

    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getField($contactSegmentFilterCrate);
    }

    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getTable($contactSegmentFilterCrate);
    }

    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($contactSegmentFilterCrate->getOperator() === '=') {
            return 'like';
        }
        if ($contactSegmentFilterCrate->getOperator() === '!=') {
            return 'notLike';
        }

        return $this->dateDecorator->getOperator($contactSegmentFilterCrate);
    }

    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $date = new \DateTime('now');
        $date->modify($this->originalValue);

        $operator = $this->getOperator($contactSegmentFilterCrate);
        $format   = 'Y-m-d';
        if ($operator === 'like' || $operator === 'notLike') {
            $format .= '%';
        }

        return $date->format($format);
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
