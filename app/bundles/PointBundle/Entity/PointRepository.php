<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class PointRepository
 */
class PointRepository extends CommonRepository
{
    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'p';
    }

    /**
     * Get array of published actions based on type
     *
     * @param string $type
     *
     * @return array
     */
    public function getPublishedByType($type)
    {
        $now = new \DateTime();
        $q = $this->createQueryBuilder('p')
            ->select('partial p.{id, type, name, properties}');

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

        return $q->getQuery()->getResult();
    }

    /**
     * @param string $type
     * @param int    $leadId
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
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }
}
