<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

final class AnyCompanyRelationValueFilterQueryBuilder extends AbstractCompanyRelationValueFilterQueryBuilder
{
    public static function getServiceId(): string
    {
        return self::class;
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
