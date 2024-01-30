<?php

namespace Mautic\ApiBundle\Entity\oAuth2;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;

/**
 * @extends CommonRepository<Client>
 */
class ClientRepository extends CommonRepository
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

    /**
     * @return Paginator<Client>
     */
    public function getEntities(array $args = []): Paginator
    {
        $q = $this
            ->createQueryBuilder('c');

        $query = $q->getQuery();

        return new Paginator($query);
    }

    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'c.name',
            'c.redirectUris',
        ]);
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
