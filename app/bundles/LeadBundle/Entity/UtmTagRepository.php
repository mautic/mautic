<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class UtmTagRepository.
 */
class UtmTagRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Get tag entities by lead.
     *
     * @param $utmTags
     *
     * @return array
     */
    public function getUtmTagsByLead(Lead $lead = null, $options = [])
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_utmtags', 'ut');

        if ($lead instanceof Lead) {
            $qb->where('ut.lead_id = '.(int) $lead->getId());
        }

        if (isset($options['search']) && $options['search']) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('ut.utm_campaign', $qb->expr()->literal('%'.$options['search'].'%')),
                $qb->expr()->like('ut.utm_content', $qb->expr()->literal('%'.$options['search'].'%')),
                $qb->expr()->like('ut.utm_medium', $qb->expr()->literal('%'.$options['search'].'%')),
                $qb->expr()->like('ut.utm_source', $qb->expr()->literal('%'.$options['search'].'%')),
                $qb->expr()->like('ut.utm_term', $qb->expr()->literal('%'.$options['search'].'%'))
            ));
        }

        return $this->getTimelineResults($qb, $options, 'ut.utm_campaign', 'ut.date_added', ['query'], ['date_added']);
    }
}
