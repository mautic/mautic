<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\Filter\AnyCompanyRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\PrimaryCompanyRelationValueFilterQueryBuilder;

class CompanyDecorator extends BaseDecorator
{
    public function getRelationJoinTable(): string
    {
        return MAUTIC_TABLE_PREFIX.'companies_leads';
    }

    public function getRelationJoinTableField(): string
    {
        return 'company_id';
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        if ($contactSegmentFilterCrate->isCompanyAllType()) {
            return AnyCompanyRelationValueFilterQueryBuilder::getServiceId();
        }

        if ($contactSegmentFilterCrate->isPrimaryCompanyType()) {
            return PrimaryCompanyRelationValueFilterQueryBuilder::getServiceId();
        }

        return ComplexRelationValueFilterQueryBuilder::getServiceId();
    }
}
