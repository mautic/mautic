<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

trait QueryBuilderManipulatorTrait
{
    private function copyParams(QueryBuilder $fromQueryBuilder, QueryBuilder $toQueryBuilder): void
    {
        foreach ($fromQueryBuilder->getParameters() as $key => $value) {
            $paramType = $fromQueryBuilder->getParameterType($key);
            // DBAL 4 expresses array parameter types with the ArrayParameterType enum
            // rather than an int above Connection::ARRAY_PARAM_OFFSET.
            if (is_array($value) && !$paramType instanceof ArrayParameterType) {
                $paramType = ArrayParameterType::STRING;
            }
            $toQueryBuilder->setParameter($key, $value, $paramType);
        }
    }
}
