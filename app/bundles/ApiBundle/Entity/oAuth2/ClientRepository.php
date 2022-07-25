<?php

namespace Mautic\ApiBundle\Entity\oAuth2;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;

/**
 * ClientRepository.
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
     * {@inheritdoc}
     */
    public function getEntities(array $args = [])
    {
        $q = $this
            ->createQueryBuilder('c');

        $query = $q->getQuery();

        return new Paginator($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'c.name',
            'c.redirectUris',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder()
    {
        return [
            ['c.name', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'c';
    }
}
