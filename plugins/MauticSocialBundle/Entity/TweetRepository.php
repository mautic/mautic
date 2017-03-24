<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class TweetRepository extends CommonRepository
{
    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $viewOther
     * @param array  $ignoreIds
     *
     * @return array
     */
    public function getEmailList($search = '', $limit = 10, $start = 0, $viewOther = false, array $ignoreIds = [])
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('partial t.{id, text, name, language}');

        if (!empty($search)) {
            if (is_array($search)) {
                $search = array_map('intval', $search);
                $qb->andWhere($qb->expr()->in('t.id', ':search'))
                    ->setParameter('search', $search);
            } else {
                $qb->andWhere($qb->expr()->like('t.name', ':search'))
                    ->setParameter('search', "%{$search}%");
            }
        }

        if (!$viewOther) {
            $qb->andWhere($qb->expr()->eq('t.createdBy', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if (!empty($ignoreIds)) {
            $qb->andWhere($qb->expr()->notIn('t.id', ':ignoreIds'))
                ->setParameter('ignoreIds', $ignoreIds);
        }

        $qb->orderBy('t.name');

        if (!empty($limit)) {
            $qb->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
