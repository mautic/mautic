<?php

namespace Mautic\WebhookBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Webhook>
 */
class WebhookRepository extends CommonRepository
{
    protected function addCatchAllWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['e.name']);
    }

    protected function addSearchCommandWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
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
