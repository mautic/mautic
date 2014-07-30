<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class AnalyticsRepository
 *
 * @package Mautic\PageBundle\Entity
 */
class AnalyticsRepository extends CommonRepository
{
    public function getHitCountForTrackingId($pageId, $trackingId)
    {
        $count = $this->createQueryBuilder('a')
            ->select('count(a.id) as num')
            ->where('IDENTITY(a.page) = ' .$pageId)
            ->andWhere('a.trackingId = :id')
            ->setParameter('id', $trackingId)
            ->getQuery()
            ->getSingleResult();

        return (int) $count['num'];
    }

    public function getBounces($pageId)
    {
        $q  = $this->createQueryBuilder('a');
        $sq = $this->createQueryBuilder('a2')
            ->select('a2.id')
            ->where('a2.page = a.page')
            ->groupBy('a2.trackingId')
            ->having('count(a2.id) = 1')
            ->getQuery()
            ->getDql();

        $q->select('a')
            ->where($q->expr()->eq('IDENTITY(a.page)', $pageId))
            ->andwhere($q->expr()->in('a.id', sprintf("%s",$sq)));
        $results = $q->getQuery()->getArrayResult();
        return $results;
    }
}
