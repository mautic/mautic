<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * ActionRepository
 */
class ActionRepository extends CommonRepository
{

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
        $q = $this->createQueryBuilder('a')
            ->select('partial a.{id, type, name, properties, settings}, partial p.{id, name}')
            ->join('a.point', 'p')
            ->orderBy('a.order');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('a.type', ':type'),
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
        $results = $q->getQuery()->getArrayResult();
        return $results;
    }
}
