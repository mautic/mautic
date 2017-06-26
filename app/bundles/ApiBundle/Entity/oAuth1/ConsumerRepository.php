<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth1;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;

/**
 * ConsumerRepository.
 */
class ConsumerRepository extends CommonRepository
{
    /**
     * @param User $user
     *
     * @return array
     */
    public function getUserClients(User $user)
    {
        $q = $this->_em->createQueryBuilder();

        $q->select('c')
            ->from('MauticApiBundle:oAuth1\Consumer', 'c')
            ->leftJoin('c.accessTokens', 'a')
            ->where($q->expr()->eq('a.user', ':user'))
            ->setParameter('user', $user)
            ->groupBy('c.id');

        return $q->getQuery()->getResult();
    }

    /**
     * @param Consumer $consumer
     * @param User     $user
     */
    public function deleteAccessTokens(Consumer $consumer, User $user)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete('MauticApiBundle:oAuth1\AccessToken', 'a')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('a.consumer', ':consumer'),
                    $qb->expr()->eq('a.user', ':user')
                )
            )
            ->setParameters([
                'consumer' => $consumer,
                'user'     => $user,
            ]);

        $qb->getQuery()->execute();
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
            'c.callback',
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
