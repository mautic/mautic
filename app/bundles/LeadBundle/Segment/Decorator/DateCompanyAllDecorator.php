<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\Filter\AnyCompanyRelationValueFilterQueryBuilder;

class DateCompanyAllDecorator extends DateCompanyDecorator
{
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return AnyCompanyRelationValueFilterQueryBuilder::getServiceId();
    }
}
