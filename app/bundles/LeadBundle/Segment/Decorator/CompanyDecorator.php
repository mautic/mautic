<?php

declare(strict_types=1);

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
        if (ContactSegmentFilterCrate::COMPANY_OBJECT === $contactSegmentFilterCrate->getObject()
            && ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE_VALUE === ($contactSegmentFilterCrate->getArray()[ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE] ?? null)
        ) {
            return ComplexRelationValueFilterQueryBuilder::getServiceId();
        }

        return match ($contactSegmentFilterCrate->getObject()) {
            ContactSegmentFilterCrate::COMPANY_ALL_OBJECT => AnyCompanyRelationValueFilterQueryBuilder::getServiceId(),
            ContactSegmentFilterCrate::COMPANY_OBJECT     => PrimaryCompanyRelationValueFilterQueryBuilder::getServiceId(),
            default                                       => ComplexRelationValueFilterQueryBuilder::getServiceId(),
        };
    }
}
