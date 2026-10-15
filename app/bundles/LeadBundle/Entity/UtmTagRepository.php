<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Doctrine\DatabasePlatform;
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
    public function getUtmTagsByLead(?Lead $lead = null, array $options = [])
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_utmtags', 'ut');

        if ($lead instanceof Lead) {
            $qb->where('ut.lead_id = '.$lead->getId());
        }

        if (isset($options['search']) && $options['search']) {
            $connection = $this->getEntityManager()->getConnection();
            $pattern    = $qb->expr()->literal('%'.$options['search'].'%');
            $qb->andWhere($qb->expr()->or(
                DatabasePlatform::getCaseInsensitiveLike($connection->getDatabasePlatform(), 'ut.utm_campaign', $pattern),
                DatabasePlatform::getCaseInsensitiveLike($connection->getDatabasePlatform(), 'ut.utm_content', $pattern),
                DatabasePlatform::getCaseInsensitiveLike($connection->getDatabasePlatform(), 'ut.utm_medium', $pattern),
                DatabasePlatform::getCaseInsensitiveLike($connection->getDatabasePlatform(), 'ut.utm_source', $pattern),
                DatabasePlatform::getCaseInsensitiveLike($connection->getDatabasePlatform(), 'ut.utm_term', $pattern)
            ));
        }

        return $this->getTimelineResults($qb, $options, 'ut.utm_campaign', 'ut.date_added', ['query'], ['date_added'], null, 'ut.id');
    }
}
