<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\Filter\AnyCompanyRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\AnyCompanyTagRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\PrimaryCompanyRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\PrimaryCompanyTagRelationValueFilterQueryBuilder;

class CompanyDecorator extends BaseDecorator
{
    private const COMPANY_TAGS_FILTER = 'company_tags';

    public function getRelationJoinTable(): string
    {
        return MAUTIC_TABLE_PREFIX.'companies_leads';
    }

    public function getRelationJoinTableField(): string
    {
        return 'company_id';
    }

    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        if (self::COMPANY_TAGS_FILTER === $contactSegmentFilterCrate->getField()) {
            return 'tag_id';
        }

        return parent::getField($contactSegmentFilterCrate);
    }

    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        if (self::COMPANY_TAGS_FILTER === $contactSegmentFilterCrate->getField()) {
            return MAUTIC_TABLE_PREFIX.Company::TAGS_XREF_TABLE_NAME;
        }

        return parent::getTable($contactSegmentFilterCrate);
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        if (self::COMPANY_TAGS_FILTER === $contactSegmentFilterCrate->getField()) {
            $queryType = $contactSegmentFilterCrate->isCompanyAllType()
                ? AnyCompanyTagRelationValueFilterQueryBuilder::getServiceId()
                : PrimaryCompanyTagRelationValueFilterQueryBuilder::getServiceId();
        } elseif ($contactSegmentFilterCrate->isCompanyAllType()) {
            $queryType = AnyCompanyRelationValueFilterQueryBuilder::getServiceId();
        } elseif ($contactSegmentFilterCrate->isPrimaryCompanyType()) {
            $queryType = PrimaryCompanyRelationValueFilterQueryBuilder::getServiceId();
        } else {
            $queryType = ComplexRelationValueFilterQueryBuilder::getServiceId();
        }

        return $queryType;
    }
}
