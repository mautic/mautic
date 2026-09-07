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
     * @return list<Client>
     */
    public function getUserClients(User $user): array
    {
        $query = $this->createQueryBuilder($this->getTableAlias());

        $query->join('c.users', 'u')
            ->where($query->expr()->eq('u.id', ':userId'))
            ->setParameter('userId', $user->getId());

        return $query->getQuery()->getResult();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param object                                                       $filter
     *
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'c.name',
            'c.redirectUris',
        ]);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param object                                                       $filter
     *
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $parameters] = parent::addSearchCommandWhereClause($q, $filter);

        if (false !== $expr) {
            return [$expr, $parameters];
        }

        $command = $filter->command;

        return match ($command) {
            $this->translator->trans('mautic.core.searchcommand.name'), $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US') => $this->addStandardCatchAllWhereClause($q, $filter, [
                $this->getTableAlias().'.name',
            ]),
            $this->translator->trans('mautic.api.client.searchcommand.callback'), $this->translator->trans('mautic.api.client.searchcommand.callback', [], null, 'en_US'), $this->translator->trans('mautic.api.client.searchcommand.redirecturi'), $this->translator->trans('mautic.api.client.searchcommand.redirecturi', [], null, 'en_US') => $this->addStandardCatchAllWhereClause($q, $filter, [
                $this->getTableAlias().'.redirectUris',
            ]),
            default => [$expr, $parameters],
        };
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

    /**
     * @return array<array<string>>
     */
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
