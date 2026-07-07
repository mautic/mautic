<?php

namespace Mautic\WebhookBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Webhook>
 */
class WebhookRepository extends CommonRepository
{
    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['e.name']);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $parameters] = parent::addSearchCommandWhereClause($q, $filter);

        if (false !== $expr) {
            return [$expr, $parameters];
        }

        $command = $filter->command;

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.name'):
            case $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US'):
                return $this->addStandardCatchAllWhereClause($q, $filter, [
                    $this->getTableAlias().'.name',
                ]);
        }

        return $this->addStandardSearchCommandWhereClause($q, $filter);
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
