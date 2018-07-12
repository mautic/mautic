<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class RedirectRepository.
 */
class RedirectRepository extends CommonRepository
{
    /**
     * @param array $urls
     *
     * @return array
     */
    public function findByUrls(array $urls)
    {
        $q = $this->createQueryBuilder('r');

        $expr = $q->expr()->andX(
            $q->expr()->in('r.url', ':urls')
        );

        $q->where($expr)
            ->setParameter('urls', $urls);

        return $q->getQuery()->getResult();
    }

    /**
     * @param array $ids
     * @param Email $email
     *
     * @return array
     */
    public function findByIds(array $ids, Email $email = null)
    {
        $q = $this->createQueryBuilder('r');

        $expr = $q->expr()->andX(
            $q->expr()->in('r.id', ':ids')
        );

        if ($email === null) {
            $expr->add(
                $q->expr()->isNull('r.email')
            );
        } else {
            $expr->add(
                $q->expr()->eq('r.email', ':email')
            );
            $q->setParameter('email', $email);
        }

        $q->where($expr)
            ->setParameter('ids', $ids);

        return $q->getQuery()->getResult();
    }

    /**
     * Up the hit count.
     *
     * @param            $id
     * @param int        $increaseBy
     * @param bool|false $unique
     */
    public function upHitCount($id, $increaseBy = 1, $unique = false)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'page_redirects')
            ->set('hits', 'hits + '.(int) $increaseBy)
            ->where('id = '.(int) $id);

        if ($unique) {
            $q->set('unique_hits', 'unique_hits + '.(int) $increaseBy);
        }

        $q->execute();
    }

    /**
     * @param int       $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int|null  $createdByUserId
     * @param int|null  $companyId
     * @param int|null  $campaignId
     * @param int|null  $segmentId
     *
     * @return array
     */
    public function getMostHitEmailRedirects(
        $limit,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        $createdByUserId = null,
        $companyId = null,
        $campaignId = null,
        $segmentId = null
    ) {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->addSelect('ph.url')
            ->addSelect('count(ph.id) as hits')
            ->addSelect('count(distinct ph.tracking_id) as unique_hits')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
            ->join('ph', MAUTIC_TABLE_PREFIX.'email_stats', 'es', 'ph.source = "email" and ph.source_id = es.email_id and ph.lead_id = es.lead_id')
            ->join('es', MAUTIC_TABLE_PREFIX.'emails', 'e', 'es.email_id = e.id')
            ->addSelect('e.id AS email_id')
            ->addSelect('e.name AS email_name');

        // Group by the page hit URL instead of redirect ID because the redirect could be a token
        $q->groupBy('ph.url, e.id, e.name, campaign.id, campaign.name');

        if ($createdByUserId !== null) {
            $q->andWhere('e.created_by = :userId')
                ->setParameter('userId', $createdByUserId);
        }

        $q->andWhere('ph.date_hit BETWEEN :dateFrom AND :dateTo')
            ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'))
            ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));

        if ($companyId !== null) {
            $sb = $this->getEntityManager()->getConnection()->createQueryBuilder();

            $sb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
                ->where(
                    $sb->expr()->andX(
                        $sb->expr()->eq('cl.company_id', ':companyId'),
                        $sb->expr()->eq('cl.lead_id', 'ph.lead_id')
                    )
                );

            $q->andWhere(
                sprintf('EXISTS (%s)', $sb->getSQL())
            )
                ->setParameter('companyId', $companyId);
        }

        $q->leftJoin('es', MAUTIC_TABLE_PREFIX.'campaign_events', 'ce', 'es.source = "campaign.event" and es.source_id = ce.id')
            ->leftJoin('ce', MAUTIC_TABLE_PREFIX.'campaigns', 'campaign', 'ce.campaign_id = campaign.id')
            ->addSelect('campaign.id AS campaign_id')
            ->addSelect('campaign.name AS campaign_name');

        if ($campaignId !== null) {
            $q->andWhere('ce.campaign_id = :campaignId')
                ->setParameter('campaignId', $campaignId);
        }

        if ($segmentId !== null) {
            $q->leftJoin('ph', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll', 'ph.lead_id = lll.lead_id')
                ->leftJoin('lll', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 'lll.leadlist_id = ll.id')
                ->addSelect('ll.id AS segment_id')
                ->addSelect('ll.name AS segment_name');

            // Prevent strict errors in MySQL 5.7
            $q->addGroupBy('ll.id, ll.name');

            $q->andWhere('lll.leadlist_id = :segmentId')
                ->setParameter('segmentId', $segmentId);
        } else {
            // Due to many-to-many relationship between contact and segment, we cannot include segments unless filtering by segment or else
            // each segment a contact is in will result in a click leading to confusing results. Imagine there is a click from a contact that is in
            // one segment and a click from a contact that is in the same plus another. The results will show 1 click for the one segment and
            // 2 clicks for the second. But there were only two clicks total and so there's no way to determine actual number of clicks.
            // Since these results are not time aware (and thus not usable in a line graph), we should not include duplicate click counts.
            $q->addSelect('null as segment_id, null as segment_name');
        }

        $q->setMaxResults($limit);

        $q->orderBy('hits', 'DESC');

        return $q->execute()->fetchAll();
    }
}
