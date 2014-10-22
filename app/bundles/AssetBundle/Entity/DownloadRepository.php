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
     * @param array   $ipIds
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadDownloads($leadId, array $ipIds = array())
    {
        $query = $this->createQueryBuilder('d')
            ->select('IDENTITY(d.asset) AS asset_id, d.dateDownload')
            ->where('d.lead = ' . $leadId);

        if (!empty($ipIds)) {
            $query->orWhere('d.ipAddress IN (' . implode(',', $ipIds) . ')');
        }

        return $query->getQuery()
            ->getArrayResult();
    }

    /**
     * Get hit count per day for last 30 days
     *
     * @param integer $assetId
     * @param integer $amount
     * @param integer $unit: php.net/manual/en/class.dateinterval.php#dateinterval.props
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDownloads($assetId, $amount = 30, $unit = 'D')
    {
        $date = new \DateTime();
        $oneUnit = new \DateInterval('P1'.$unit);
        $data = array('labels' => array(), 'values' => array());

        // Prefill $data arrays
        for ($i = 0; $i < 30; $i++) {
            $data['labels'][$i] = $date->format('Y-m-d');
            $data['values'][$i] = 0;
            $date->sub($oneUnit);
        }
        
        $query = $this->createQueryBuilder('d');
        
        $query->select('IDENTITY(d.asset), d.dateDownload')
            ->where($query->expr()->eq('IDENTITY(d.asset)', (int) $assetId))
            ->andwhere($query->expr()->gte('d.dateDownload', ':date'))
            ->setParameter('date', $date);

        $downloads = $query->getQuery()->getArrayResult();

        // Group hits by date
        foreach ($downloads as $download) {
            $oneItem = $download['dateDownload']->format('Y-m-d');
            if (($itemKey = array_search($oneItem, $data['labels'])) !== false) {
                $data['values'][$itemKey]++;
            }
        }

        $data['values'] = array_reverse($data['values']);
        $data['labels'] = array_reverse($data['labels']);

        return $data;
    }
}
