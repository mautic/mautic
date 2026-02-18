<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

final class AnyCompanyRelationValueFilterQueryBuilder extends PrimaryCompanyRelationValueFilterQueryBuilder
{
    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.complex_relation.any_company';
    }

    protected function shouldAllowMissingCompany(string $filterOperator): bool
    {
        return false;
    }

    protected function requiresPrimaryCompany(): bool
    {
        return false;
    }
}
