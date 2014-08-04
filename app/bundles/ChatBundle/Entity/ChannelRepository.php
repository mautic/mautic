<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChatBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\Role;
/**
 * ChannelRepository
 */
class ChannelRepository extends CommonRepository
{

    /**
     * @param $userId
     *
     * @return array
     */
    public function getUserChannels($userId)
    {
        $q = $this->createQueryBuilder('c')
            ->leftJoin('c.privateUsers', 'u');

        $q->select('partial c.{id, name}')
            ->where(
                $q->expr()->orX(
                    $q->expr()->eq('c.isPrivate', 0),
                    $q->expr()->andX(
                        $q->expr()->eq('c.isPrivate', 1),
                        $q->expr()->eq('u.id', ':userId')
                    )
                )
            )
            ->setParameter('userId', $userId)
            ->orderBy('c.name', 'ASC');

        $results = $q->getQuery()->getArrayResult();
        return $results;
    }


    /**
     * Retrieves array of names used to ensure unique name
     *
     * @param $exludingId
     * @return array
     */
    public function getNames($exludingId)
    {
        $q = $this->createQueryBuilder('c')
            ->select('c.name');
        if (!empty($exludingId)) {
            $q->where('c.id != :id')
                ->setParameter('id', $exludingId);
        }

        $results = $q->getQuery()->getArrayResult();
        $names = array();
        foreach($results as $item) {
            $names[] = $item['name'];
        }

        return $names;
    }

    /**
     * @param $channelId
     */
    public function archiveChannel($channelId)
    {
        $this->_em->getConnection()
            ->update(MAUTIC_TABLE_PREFIX . 'channels', array('is_published' => 0), array('id' => $channelId));
    }
}
