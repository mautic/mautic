<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Webhook>
 */
class WebhookRepository extends CommonRepository
{
    /**
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected function addCatchAllWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    {
        return $this->addStandardCatchAllWhereClause($queryBuilder, $filter, ['e.name']);
    }

    protected function addSearchCommandWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    {
        [$expr, $parameters] = parent::addSearchCommandWhereClause($queryBuilder, $filter);

        if (false !== $expr) {
            return [$expr, $parameters];
        }

        $command = $filter->command;

        return match ($command) {
            $this->translator->trans('mautic.core.searchcommand.name'), $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US') => $this->addStandardCatchAllWhereClause($queryBuilder, $filter, [
                $this->getTableAlias().'.name',
            ]),
            default => $this->addStandardSearchCommandWhereClause($queryBuilder, $filter),
        };
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge(
            ['mautic.core.searchcommand.name'],
            $this->getStandardSearchCommands()
        );
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
