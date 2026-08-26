<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Webhook>
 */
class WebhookRepository extends CommonRepository
{
    protected function addCatchAllWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    {
        return $this->addStandardCatchAllWhereClause($queryBuilder, $filter, ['e.name']);
    }

    protected function addSearchCommandWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($queryBuilder, $filter);
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.name', 'ASC'],
        ];
    }
}
