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
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class PointRepository
 *
 * @package Mautic\PointBundle\Entity
 */
class PointRepository extends CommonRepository
{
    public function getTableAlias()
    {
        return 'p';
    }

    /**
     * Get array of published actions based on type
     *
     * @param $type
     *
     * @return array
     */
    public function getPublishedByType($type)
    {
        $now = new \DateTime();
        $q = $this->createQueryBuilder('p')
            ->select('partial p.{id, type, name, properties}')
            ->orderBy('p.order');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('p.type', ':type'),
                $q->expr()->eq('p.isPublished', true),
                $q->expr()->orX(
                    $q->expr()->isNull('p.publishUp'),
                    $q->expr()->gte('p.publishUp', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('p.publishDown'),
                    $q->expr()->lte('p.publishDown', ':now')
                )
            )
        )
            ->setParameter('now', $now)
            ->setParameter('type', $type);

        $results = $q->getQuery()->getResult();
        return $results;
    }

    /**
     * @param $type
     * @param $leadId
     *
     * @return array
     */
    public function getCompletedLeadActions($type, $leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('p.*')
            ->from(MAUTIC_TABLE_PREFIX . 'point_lead_action_log', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX . 'points', 'p', 'x.point_id = p.id');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('p.type', ':type'),
                $q->expr()->eq('x.lead_id', $leadId)
            )
        )
            ->setParameter('type', $type);

        $results = $q->execute()->fetchAll();

        $return = array();
        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter, array(
            'p.name',
            'p.description'
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
