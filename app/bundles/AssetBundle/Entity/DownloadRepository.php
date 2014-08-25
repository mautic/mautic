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
}
