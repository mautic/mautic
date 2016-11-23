<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;


/**
 * Class SnapshotRepository
 *
 * @package Mautic\FeedBundle\Entity
 */
class SnapshotRepository extends CommonRepository
{
    /**
     *
     * @param Snapshot $snapshot
     * @param \DateTime $date
     */
    public function expireSnapshot(Snapshot $snapshot, \DateTime $date=null){
        if (is_null($date)){
            $date = new \DateTime();
        }

        $snapshot->setDateExpired($date);

        $this->_em->persist($snapshot);

        return $snapshot;

    }
    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 's';
    }
}
