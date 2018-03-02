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

class DateAnniversary implements FilterDecoratorInterface
{
    /**
     * @var DateDecorator
     */
    private $dateDecorator;

    public function __construct(DateDecorator $dateDecorator)
    {
        $this->dateDecorator = $dateDecorator;
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
        return 'like';
    }

    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $dateTimeHelper = $this->dateDecorator->getDefaultDate();

        return $dateTimeHelper->toUtcString('%-m-d');

        return '%'.date('-m-d');
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
