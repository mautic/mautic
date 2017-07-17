<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @param User $user
     *
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
