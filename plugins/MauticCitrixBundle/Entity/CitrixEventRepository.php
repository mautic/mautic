<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

class CitrixEventRepository extends CommonRepository
{
    /**
     * Fetch the base event data from the database.
     *
     * @param string    $product
     * @param string    $eventType
     * @param \DateTime $fromDate
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getEvents($product, $eventType, \DateTime $fromDate = null)
    {
        $q = $this->createQueryBuilder('c');

        $expr = $q->expr()->andX(
            $q->expr()->eq('c.product', ':product'),
            $q->expr()->eq('c.event_type', ':eventType')
        );

        if ($fromDate) {
            $expr->add(
                $q->expr()->gte('c.event_date', ':fromDate')
            );
            $q->setParameter('fromDate', $fromDate);
        }

        $q->where($expr)
            ->setParameter('eventType', $eventType)
            ->setParameter('product', $product);

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @param string $product
     * @param string $email
     *
     * @return array
     */
    public function findByEmail($product, $email)
    {
        return $this->findBy(
            [
                'product' => $product,
                'email'   => $email,
            ]
        );
    }

    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        $q = $this->_em
            ->createQueryBuilder()
            ->select($alias)
            ->from('MauticCitrixBundle:CitrixEvent', $alias, $alias.'.id');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['c.product', 'c.email', 'c.eventType', 'c.eventName']);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return [
            [$this->getTableAlias().'.eventDate', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'c';
    }
}
