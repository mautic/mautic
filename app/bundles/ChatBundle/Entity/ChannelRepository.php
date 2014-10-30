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

        //get a list of total chats and unread chats
        $qb = $this->_em->createQueryBuilder();
        $qb->select('c.id, u.id, s.lastRead, s.dateRead')
            ->from('MauticChatBundle:ChannelStat', 's')
            ->leftJoin('s.channel', 'c')
            ->leftJoin('s.user', 'u')
            ->where(
                $q->expr()->eq('IDENTITY(s.user)', ':user')
            )
            ->setParameter('user', $userId)
            ->andWhere(
                $qb->expr()->in('s.channel', ':ids')
            )
            ->setParameter('ids', $ids);
        $stats   = array();
        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as $r) {
            $stats[$r['id']] = $r;
        }
        unset($results);

        unset($qb);

        $expr = $q->expr()->andX(
            $q->expr()->in('IDENTITY(c.channel)', ':ids'),
            $q->expr()->notIn('c.fromUser', ':userId')
        );

        $qb = $this->_em->createQueryBuilder();
        $qb->select('c.id, ch.id as channelId')
            ->from('MauticChatBundle:Chat', 'c')
            ->leftJoin('c.channel', 'ch')
            ->where($expr)
            ->setParameter('ids', $ids)
            ->setParameter('userId', $userId)
            ->orderBy('c.id', 'ASC');
        $counts  = array();
        $results = $qb->getQuery()->getArrayResult();
        foreach($results as $r) {
            $counts[$r['channelId']][] = $r['id'];
        }
        unset($results);

        foreach ($channels as &$c) {
            //get the total
            $c['stats'] = array(
                'total' => (isset($counts[$c['id']])) ? count($counts[$c['id']]) : 0
            );

            //get unread
            if (isset($stats[$c['id']])) {
                $lastRead = $stats[$c['id']]['lastRead'];
                $key      = array_search($lastRead, $counts[$c['id']]);
                $c['stats']['unread'] = $c['stats']['total'] - ($key + 1);
            } else {
                $c['stats']['unread'] = $c['stats']['total'];
            }
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