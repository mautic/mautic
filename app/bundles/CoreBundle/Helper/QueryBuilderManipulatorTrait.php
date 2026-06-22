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
            if (is_array($value) && (!is_int($paramType) || $paramType < Connection::ARRAY_PARAM_OFFSET)) {
                $paramType = ArrayParameterType::STRING;
            }
            $toQueryBuilder->setParameter($key, $value, $paramType);
        }
    }
}
