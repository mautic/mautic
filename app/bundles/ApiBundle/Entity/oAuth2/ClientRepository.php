<?php

namespace Mautic\ApiBundle\Entity\oAuth2;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;

/**
 * @extends CommonRepository<Client>
 */
final class ClientRepository extends CommonRepository
{
    /**
     * @return array
     */
    public function getUserClients(User $user)
    {
        $query = $this->createQueryBuilder($this->getTableAlias());

        $query->join('c.users', 'u')
            ->where($query->expr()->eq('u.id', ':userId'))
            ->setParameter('userId', $user->getId());

        return $query->getQuery()->getResult();
    }

    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'c.name',
            'c.redirectUris',
        ]);
    }

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
            case $this->translator->trans('mautic.api.client.searchcommand.callback'):
            case $this->translator->trans('mautic.api.client.searchcommand.callback', [], null, 'en_US'):
            case $this->translator->trans('mautic.api.client.searchcommand.redirecturi'):
            case $this->translator->trans('mautic.api.client.searchcommand.redirecturi', [], null, 'en_US'):
                return $this->addStandardCatchAllWhereClause($q, $filter, [
                    $this->getTableAlias().'.redirectUris',
                ]);
        }

        return [$expr, $parameters];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge([
            'mautic.core.searchcommand.name',
            'mautic.api.client.searchcommand.callback',
            'mautic.api.client.searchcommand.redirecturi',
        ], parent::getSearchCommands());
    }

    protected function getDefaultOrder(): array
    {
        return [
            ['c.name', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'c';
    }
}
