<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

final class PrimaryCompanyRelationValueFilterQueryBuilder extends AbstractCompanyRelationValueFilterQueryBuilder
{
    public static function getServiceId(): string
    {
        return self::class;
    }
}
