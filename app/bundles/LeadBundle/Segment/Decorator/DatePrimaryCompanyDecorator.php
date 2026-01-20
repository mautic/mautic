<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\Filter\PrimaryCompanyRelationValueFilterQueryBuilder;

class DatePrimaryCompanyDecorator extends DateCompanyDecorator
{
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return PrimaryCompanyRelationValueFilterQueryBuilder::getServiceId();
    }
}
