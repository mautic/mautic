<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * Class DownloadRepository.
 */
class DownloadRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Determine if the download is a unique download.
     *
     * @param $assetId
     * @param $trackingId
     *
     * @return bool
     */
    public function isUniqueDownload($assetId, $trackingId)
    {
        $q  = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q2 = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q2->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'asset_downloads', 'd');

        $q2->where(
            $q2->expr()->andX(
                $q2->expr()->eq('d.tracking_id', ':id'),
                $q2->expr()->eq('d.asset_id', (int) $assetId)
            )
        );

        $q->select('u.is_unique')
            ->from(sprintf('(SELECT (NOT EXISTS (%s)) is_unique)', $q2->getSQL()), 'u'
            )
            ->setParameter('id', $trackingId);

        return (bool) $q->execute()->fetchColumn();
    }

    /**
     * Get a lead's page downloads.
     *
     * @param int|null $leadId
     * @param array    $options
     *
     * @return array
     */
    public function getLeadDownloads($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('a.id as asset_id, d.date_download as dateDownload, a.title')
            ->from(MAUTIC_TABLE_PREFIX.'asset_downloads', 'd')
            ->leftJoin('d', MAUTIC_TABLE_PREFIX.'assets', 'a', 'd.asset_id = a.id');

        if ($leadId) {
            $query->where('d.lead_id = '.(int) $leadId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere($query->expr()->like('a.title', $query->expr()->literal('%'.$options['search'].'%')));
        }

        return $this->getTimelineResults($query, $options, 'a.title', 'd.date_download', [], ['date_download']);
    }

    /**
     * Get list of assets ordered by it's download count.
     *
     * @param QueryBuilder $query
     * @param int          $limit
     * @param int          $offset
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostDownloaded($query, $limit = 10, $offset = 0)
    {
        $query->select('a.title, a.id, count(ad.id) as downloads')
            ->groupBy('a.id, a.title')
            ->orderBy('downloads', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * Get list of asset referrals ordered by it's count.
     *
     * @param QueryBuilder $query
     * @param int          $limit
     * @param int          $offset
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTopReferrers($query, $limit = 10, $offset = 0)
    {
        $query->select('ad.referer, count(ad.referer) as downloads')
            ->groupBy('ad.referer')
            ->orderBy('downloads', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * Get pie graph data for http statuses.
     *
     * @param QueryBuilder $query
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getHttpStatuses($query)
    {
        $query->select('ad.code as status, count(ad.code) as count')
            ->groupBy('ad.code')
            ->orderBy('count', 'DESC');

        $results = $query->execute()->fetchAll();
        $chart   = new PieChart();

        foreach ($results as $result) {
            $chart->setDataset($result['status'], $result['count']);
        }

        return $chart->render();
    }

    /**
     * @param           $pageId
     * @param \DateTime $fromDate
     *
     * @return mixed
     */
    public function getDownloadCountsByPage($pageId, \DateTime $fromDate = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('count(distinct(a.tracking_id)) as count, a.source_id as id, p.title as name, p.hits as total')
            ->from(MAUTIC_TABLE_PREFIX.'asset_downloads', 'a')
            ->join('a', MAUTIC_TABLE_PREFIX.'pages', 'p', 'a.source_id = p.id');

        if (is_array($pageId)) {
            $q->where($q->expr()->in('p.id', $pageId))
                ->groupBy('p.id, a.source_id, p.title, p.hits');
        } else {
            $q->where($q->expr()->eq('p.id', ':page'))
                ->setParameter('page', (int) $pageId);
        }

        $q->andWhere('a.source = "page"')
            ->andWhere('a.code = 200');

        if ($fromDate != null) {
            $dh = new DateTimeHelper($fromDate);
            $q->andWhere($q->expr()->gte('a.date_download', ':date'))
                ->setParameter('date', $dh->toUtcString());
        }

        $results = $q->execute()->fetchAll();

        $downloads = [];
        foreach ($results as $r) {
            $downloads[$r['id']] = $r;
        }

        return $downloads;
    }

    /**
     * Get download count by email by linking emails that have been associated with a page hit that has the
     * same tracking ID as an asset download tracking ID and thus assumed happened in the same session.
     *
     * @param           $emailId
     * @param \DateTime $fromDate
     *
     * @return mixed
     */
    public function getDownloadCountsByEmail($emailId, \DateTime $fromDate = null)
    {
        //link email to page hit tracking id to download tracking id
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('count(distinct(a.tracking_id)) as count, e.id, e.subject as name, e.variant_sent_count as total')
            ->from(MAUTIC_TABLE_PREFIX.'asset_downloads', 'a')
            ->join('a', MAUTIC_TABLE_PREFIX.'emails', 'e', 'a.email_id = e.id');

        if (is_array($emailId)) {
            $q->where($q->expr()->in('e.id', $emailId))
                ->groupBy('e.id, e.subject, e.variant_sent_count');
        } else {
            $q->where($q->expr()->eq('e.id', ':email'))
                ->setParameter('email', (int) $emailId);
        }

        $q->andWhere('a.code = 200');

        if ($fromDate != null) {
            $dh = new DateTimeHelper($fromDate);
            $q->andWhere($q->expr()->gte('a.date_download', ':date'))
                ->setParameter('date', $dh->toUtcString());
        }

        $results = $q->execute()->fetchAll();

        $downloads = [];
        foreach ($results as $r) {
            $downloads[$r['id']] = $r;
        }

        return $downloads;
    }

    /**
     * @param $leadId
     * @param $newTrackingId
     * @param $oldTrackingId
     */
    public function updateLeadByTrackingId($leadId, $newTrackingId, $oldTrackingId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'asset_downloads')
            ->set('lead_id', (int) $leadId)
            ->set('tracking_id', ':newTrackingId')
            ->where(
                $q->expr()->eq('tracking_id', ':oldTrackingId')
            )
            ->setParameters([
                'newTrackingId' => $newTrackingId,
                'oldTrackingId' => $oldTrackingId,
            ])
            ->execute();
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'asset_downloads')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->execute();
    }
}
