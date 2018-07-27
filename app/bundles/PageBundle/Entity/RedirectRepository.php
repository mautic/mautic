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
        $q->addSelect('pr.url')
            ->addSelect('count(ph.id) as hits')
            ->addSelect('count(distinct ph.tracking_id) as unique_hits')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
            ->join('ph', MAUTIC_TABLE_PREFIX.'page_redirects', 'pr', 'pr.id = ph.redirect_id')
            ->join('ph', MAUTIC_TABLE_PREFIX.'email_stats', 'es', 'ph.source = "email" and ph.source_id = es.email_id and ph.lead_id = es.lead_id')
            ->join('es', MAUTIC_TABLE_PREFIX.'emails', 'e', 'es.email_id = e.id')
            ->addSelect('e.id AS email_id')
            ->addSelect('e.name AS email_name');

        if ($createdByUserId !== null) {
            $q->andWhere('e.created_by = :userId')
                ->setParameter('userId', $createdByUserId);
        }

        $q->andWhere('ph.date_hit BETWEEN :dateFrom AND :dateTo')
            ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'))
            ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));

        $q->leftJoin('es', MAUTIC_TABLE_PREFIX.'campaign_events', 'ce', 'es.source = "campaign.event" and es.source_id = ce.id')
            ->leftJoin('ce', MAUTIC_TABLE_PREFIX.'campaigns', 'campaign', 'ce.campaign_id = campaign.id')
            ->addSelect('campaign.id AS campaign_id')
            ->addSelect('campaign.name AS campaign_name');

        if ($campaignId !== null) {
            $q->andWhere('ce.campaign_id = :campaignId')
                ->setParameter('campaignId', $campaignId);
        }

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

        if ($segmentId !== null) {
            $sb = $this->getEntityManager()->getConnection()->createQueryBuilder();

            $sb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll')
                ->where(
                    $sb->expr()->andX(
                        $sb->expr()->eq('lll.leadlist_id', ':segmentId'),
                        $sb->expr()->eq('lll.lead_id', 'ph.lead_id'),
                        $sb->expr()->eq('lll.manually_removed', 0)
                    )
                );

            $q->andWhere(
                sprintf('EXISTS (%s)', $sb->getSQL())
            )
                ->setParameter('segmentId', $segmentId);
        }

        $q->groupBy('pr.id, pr.url, e.id, e.name, campaign.id, campaign.name');

        $q->setMaxResults($limit);

        $q->orderBy('hits', 'DESC');

        return $q->execute()->fetchAll();
    }
}
