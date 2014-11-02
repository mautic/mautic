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
        $data = $this->prepareDownloadsGraphDataBefore($amount, $unit);
        
        $query = $this->createQueryBuilder('d');
        
        $query->select('IDENTITY(d.asset), d.dateDownload')
            ->where($query->expr()->eq('IDENTITY(d.asset)', (int) $assetId))
            ->andwhere($query->expr()->gte('d.dateDownload', ':date'))
            ->setParameter('date', $data['fromDate']);

        $downloads = $query->getQuery()->getArrayResult();

        return $this->prepareDownloadsGraphDataAfter($data, $downloads, $unit);
    }

    /**
     * Get proper date label format depending on what date scope we want to display
     *
     * @param char $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return string
     */
    public function getDateLabelFromat($unit = 'D')
    {
        $format = '';
        if ($unit == 'H') {
            $format = 'H:00';
        } elseif ($unit == 'D') {
            $format = 'jS F';
        } elseif ($unit == 'W') {
            $format = 'W';
        } elseif ($unit == 'M') {
            $format = 'F y';
        } elseif ($unit == 'Y') {
            $format = 'Y';
        }
        return $format;
    }

    /**
     * Prepares data structure of labels and values needed for line graph.
     * fromDate variable can be used for SQL query as a limit.
     *
     * @param integer $amount of units
     * @param char $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return array
     */
    public function prepareDownloadsGraphDataBefore($amount = 30, $unit = 'D')
    {
        $isTime = '';

        if ($unit == 'H') {
            $isTime = 'T';
        }

        $format = $this->getDateLabelFromat($unit);

        $date = new \DateTime();
        $oneUnit = new \DateInterval('P'.$isTime.'1'.$unit);
        $data = array('labels' => array(), 'values' => array());

        // Prefill $data arrays
        for ($i = 0; $i < $amount; $i++) {
            $data['labels'][$i] = $date->format($format);
            $data['values'][$i] = 0;
            $date->sub($oneUnit);
        }

        $data['fromDate'] = $date;

        return $data;
    }

    /**
     * Fills into graph data values grouped by time unit
     *
     * @param array $data from prepareDownloadsGraphDataBefore
     * @param array $downloads from database
     * @param char $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return array
     */
    public function prepareDownloadsGraphDataAfter($data, $downloads, $unit)
    {
        // Group hits by date
        foreach ($downloads as $download) {
            if (is_string($download['dateDownload'])) {
                $download['dateDownload'] = new \DateTime($download['dateDownload']);
            }

            $oneItem = $download['dateDownload']->format($this->getDateLabelFromat($unit));
            if (($itemKey = array_search($oneItem, $data['labels'])) !== false) {
                $data['values'][$itemKey]++;
            }
        }

        $data['values'] = array_reverse($data['values']);
        $data['labels'] = array_reverse($data['labels']);

        return $data;
    }
}
