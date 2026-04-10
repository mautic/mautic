<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

trait RelatedEntityAliasFilterRepositoryTrait
{
    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function handleRelatedEntityAliasFilter(QueryBuilder $queryBuilder, string $relation, string $joinAlias, string $value, string $parameterName): array
    {
        $resolvedJoinAlias = $this->getRelatedEntityJoinAlias($queryBuilder, $relation, $joinAlias);

        return [
            $queryBuilder->expr()->eq($resolvedJoinAlias.'.alias', ":$parameterName"),
            [$parameterName => $value],
        ];
    }

    private function getRelatedEntityJoinAlias(QueryBuilder $queryBuilder, string $relation, string $joinAlias): string
    {
        $fromAlias = $this->getTableAlias();

        foreach ($queryBuilder->getDQLPart('join')[$fromAlias] ?? [] as $join) {
            if ($join instanceof Join && $join->getJoin() === $fromAlias.'.'.$relation) {
                return $join->getAlias();
            }
        }

        $queryBuilder->leftJoin($fromAlias.'.'.$relation, $joinAlias);

        return $joinAlias;
    }
}
