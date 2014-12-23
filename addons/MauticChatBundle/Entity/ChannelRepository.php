<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;

/**
 * ChannelRepository
 */
class ChannelRepository extends CommonRepository
{

    /**
     * Gets group messages form a channel
     *
     * @param $channel
     * @param $lastId
     * @param $fromDateTime
     *
     * @return array
     */
    public function getChannelConversation($channel, $lastId = null, \DateTime $fromDateTime = null)
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('m, partial u.{id, firstName, lastName, email, lastActive, onlineStatus}')
            ->from('MauticChatBundle:Chat', 'm', 'm.id')
            ->join('m.fromUser', 'u')
            ->join('m.channel', 'c')
            ->where(
                $q->expr()->eq('c.id', $channel->getId())
            )
            ->orderBy('m.dateSent', 'ASC');

        if ($lastId !== null) {
            $q->andWhere(
                $q->expr()->gt('m.id', ':lastId')
            )->setParameter(':lastId', $lastId);
        } elseif ($fromDateTime !== null) {
            $q->andWhere(
                $q->expr()->gte('m.dateSent', ':fromDate')
            )->setParameter(':fromDate', $fromDateTime);
        }

        $results = $q->getQuery()->getArrayResult();

        //fix online statuses
        $dt    = new DateTimeHelper(strtotime('15 minutes ago'), 'U', 'local');
        $delay = $dt->getUtcDateTime();
        foreach ($results as &$r) {
            if (empty($r['fromUser']['onlineStatus'])) {
                $r['fromUser']['onlineStatus'] = ($r['fromUser']['lastActive'] >= $delay) ? 'online' : 'offline';
            } elseif (!empty($r['fromUser']['onlineStatus']) && $r['fromUser']['lastActive'] < $delay) {
                $r['fromUser']['onlineStatus'] = 'offline';
            }
        }

        return $results;
    }

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

        $channels = array();
        foreach ($results as $r) {
            $channels[$r['id']] = $r;
        }
        $ids = array_keys($channels);
        unset($results);

        $qb = $this->_em->createQueryBuilder();
        $qb->select('count(c.id) as unread, ch.id')
            ->from('MauticChatBundle:Chat', 'c')
            ->join('c.channel', 'ch')
            ->leftJoin('MauticChatBundle:ChannelStat', 's', 'WITH', 's.channel = ch.id')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('IDENTITY(c.channel)', ':ids'),
                    $qb->expr()->neq('IDENTITY(c.fromUser)', ':userId'),
                    $qb->expr()->gt('c.id', 's.lastRead')
                )
            )
            ->setParameter('ids', $ids)
            ->setParameter('userId', $userId);

        $results = $qb->getQuery()->getArrayResult();
        $unread  = array();
        foreach ($results as $r) {
            $unread[$r['id']] = $r['unread'];
        }

        foreach ($channels as &$c) {
            //get unread
            $c['unread'] = (isset($unread[$c['id']])) ? $unread[$c['id']] : 0;
        }

        return $channels;
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

    /**
     * Note last ID read for user in channel
     *
     * @param User $userId
     * @param Channel $channelId
     * @param $lastId
     */
    public function markRead(User $user, Channel $channel, $lastId)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('s')
            ->from('MauticChatBundle:ChannelStat', 's')
            ->where(
                $qb->expr()->eq('s.channel', ':channel')
            )
            ->setParameter('channel', $channel)
            ->andWhere(
                $qb->expr()->eq('s.user', ':user')
            )
            ->setParameter('user', $user);

        $exists = $qb->getQuery()->getResult();

        $now = new DateTimeHelper();

        if (!empty($exists)) {
            //update the record
            $exists[0]->setLastRead($lastId);
            $exists[0]->setDateRead($now->getDateTime());
            $this->_em->persist($exists[0]);
        } else {
            $stat = new ChannelStat();
            $stat->setChannel($channel);
            $stat->setUser($user);
            $stat->setLastRead($lastId);
            $stat->setDateRead($now->getDateTime());
            $this->_em->persist($stat);
        }
        $this->_em->flush();
    }

    /**
     * @param User    $user
     * @param Channel $channel
     *
     * @return array
     */
    public function getChannelStatForUser(User $user, Channel $channel = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('s')
            ->from('MauticChatBundle:ChannelStat', 's')
            ->where(
                $qb->expr()->eq('s.user', ':user')
            )
            ->setParameter('user', $user);

        if ($channel != null) {
            $qb->andWhere(
                $qb->expr()->eq('s.channel', ':channel')
            )
                ->setParameter('channel', $channel);
        }

        $results = $qb->getQuery()->getArrayResult();

        return (count($results)) ? $results[0] : array();
    }
}