<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class TriggerRepository
 *
 * @package Mautic\PointBundle\Entity
 */
class TriggerRepository extends CommonRepository
{
    /**
     * Get a list of published triggers with color and points
     *
     * @return array
     */
    public function getTriggerColors()
    {
        $now = new \DateTime();

        $q = $this->_em->createQueryBuilder()
            ->select('partial t.{id, color, points}')
            ->from('MauticPointBundle:Trigger', 't', 't.id');

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('t.isPublished', true),
                $q->expr()->orX(
                    $q->expr()->isNull('t.publishUp'),
                    $q->expr()->gte('t.publishUp', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('t.publishDown'),
                    $q->expr()->lte('t.publishDown', ':now')
                )
            )
        )
            ->setParameter('now', $now);

        $q->orderBy('t.points', 'ASC');

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    public function getTableAlias()
    {
        return 't';
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, array(
            't.name',
            't.description'
        ));
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
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
}
