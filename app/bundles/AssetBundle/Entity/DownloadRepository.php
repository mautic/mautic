<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\GraphHelper;

/**
 * Class DownloadRepository
 *
 * @package Mautic\AssetBundle\Entity
 */
class DownloadRepository extends CommonRepository
{

    /**
     * Get a count of unique downloads for the current tracking ID
     *
     * @param $assetId
     * @param $trackingId
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDownloadCountForTrackingId($assetId, $trackingId)
    {
        $count = $this->createQueryBuilder('d')
            ->select('count(d.id) as num')
            ->where('IDENTITY(d.asset) = ' .$assetId)
            ->andWhere('d.trackingId = :id')
            ->setParameter('id', $trackingId)
            ->getQuery()
            ->getSingleResult();

        return (int) $count['num'];
    }

    /**
     * Get a lead's page downloads
     *
     * @param integer $leadId
     * @param array   $options
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadDownloads($leadId, array $options = array())
    {
        $query = $this->createQueryBuilder('d')
            ->select('IDENTITY(d.asset) AS asset_id, d.dateDownload')
            ->where('d.lead = ' . $leadId);

        if (!empty($options['ipIds'])) {
            $query->orWhere('d.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['filters']['search']) && $options['filters']['search']) {
            $query->leftJoin('d.asset', 'a')
                ->andWhere($query->expr()->like('a.title', $query->expr()->literal('%' . $options['filters']['search'] . '%')));
        }

        return $query->getQuery()
            ->getArrayResult();
    }

    /**
     * Get hit count per day for last 30 days
     *
     * @param integer $assetId
     * @param integer $amount of units
     * @param char $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDownloads($assetId, $amount = 30, $unit = 'D')
    {
        $data = GraphHelper::prepareLineGraphData($amount, $unit);
        
        $query = $this->createQueryBuilder('d');
        
        $query->select('IDENTITY(d.asset), d.dateDownload')
            ->where($query->expr()->eq('IDENTITY(d.asset)', (int) $assetId))
            ->andwhere($query->expr()->gte('d.dateDownload', ':date'))
            ->setParameter('date', $data['fromDate']);

        $downloads = $query->getQuery()->getArrayResult();

        return GraphHelper::mergeLineGraphData($data, $downloads, $unit, 'dateDownload');
    }

    /**
     * Get list of assets ordered by it's download count
     *
     * @param QueryBuilder $query
     * @param integer $limit
     * @param integer $offset
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostDownloaded($query, $limit = 10, $offset = 0)
    {
        $query->select('a.title, a.id, count(ad.id) as downloads')
            ->from(MAUTIC_TABLE_PREFIX.'assets', 'a')
            ->leftJoin('a', MAUTIC_TABLE_PREFIX.'asset_downloads', 'ad', 'ad.asset_id = a.id')
            ->groupBy('a.id')
            ->orderBy('downloads', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * Get list of asset referrals ordered by it's count
     *
     * @param QueryBuilder $query
     * @param integer $limit
     * @param integer $offset
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTopReferrers($query, $limit = 10, $offset = 0)
    {
        $query->select('ad.referer, count(ad.referer) as downloads')
            ->from(MAUTIC_TABLE_PREFIX.'assets', 'a')
            ->leftJoin('a', MAUTIC_TABLE_PREFIX.'asset_downloads', 'ad', 'ad.asset_id = a.id')
            ->groupBy('ad.referer')
            ->orderBy('downloads', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * Get pie graph data for http statuses
     *
     * @param QueryBuilder $query
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getHttpStatuses($query)
    {
        $query->select('ad.code as status, count(ad.code) as count')
            ->from(MAUTIC_TABLE_PREFIX.'assets', 'a')
            ->leftJoin('a', MAUTIC_TABLE_PREFIX.'asset_downloads', 'ad', 'ad.asset_id = a.id')
            ->groupBy('ad.code')
            ->orderBy('count', 'DESC');

        $results = $query->execute()->fetchAll();

        $colors = GraphHelper::$colors;
        $graphData = array();
        $i = 0;
        foreach($results as $result) {
            if (!isset($colors[$i])) {
                $i = 0;
            }
            $color = $colors[$i];
            $graphData[] = array(
                'label' => $result['status'],
                'color' => $colors[$i]['color'],
                'highlight' => $colors[$i]['highlight'],
                'value' => (int) $result['count']
            );
            $i++;
        }

        return $graphData;
    }
}
