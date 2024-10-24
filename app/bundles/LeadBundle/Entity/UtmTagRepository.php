<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<UtmTag>
 */
class UtmTagRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Get tag entities by lead.
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
            $qb->andWhere($qb->expr()->or(
                $qb->expr()->like('ut.utm_campaign', $qb->expr()->literal('%'.$options['search'].'%')),
                $qb->expr()->like('ut.utm_content', $qb->expr()->literal('%'.$options['search'].'%')),
                $qb->expr()->like('ut.utm_medium', $qb->expr()->literal('%'.$options['search'].'%')),
                $qb->expr()->like('ut.utm_source', $qb->expr()->literal('%'.$options['search'].'%')),
                $qb->expr()->like('ut.utm_term', $qb->expr()->literal('%'.$options['search'].'%'))
            ));
        }

        return $this->getTimelineResults($qb, $options, 'ut.utm_campaign', 'ut.date_added', ['query'], ['date_added'], null, 'ut.id');
    }
}
