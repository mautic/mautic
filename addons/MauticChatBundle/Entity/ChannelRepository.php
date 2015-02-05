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
use Doctrine\ORM\Tools\Pagination\Paginator;
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
     * Get a list of entities
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this
            ->createQueryBuilder('c')
            ->select('c')
            ->leftJoin('c.privateUsers', 'u')
            ->leftJoin('c.stats', 's', 'WITH', 'IDENTITY(s.user) = :userId');

        $this->buildClauses($q, $args);

        $q->andWhere(
            $q->expr()->andX(
                $q->expr()->orX(
                    $q->expr()->eq('c.isPrivate', 0),
                    $q->expr()->andX(
                        $q->expr()->eq('c.isPrivate', 1),
                        $q->expr()->eq('u.id', ':userId')
                    )
                )
            )
        )->setParameter('userId', $args['userId']);

        $query = $q->getQuery();

        if (isset($args['hydration_mode'])) {
            $mode = strtoupper($args['hydration_mode']);
            $query->setHydrationMode(constant("\\Doctrine\\ORM\\Query::$mode"));
        }

        return new Paginator($query);
    }
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
    public function getUserChannels($userId, $search = '', $limit = 10, $start = 0)
    {
        $q = $this->_em->createQueryBuilder();

        $select = 'partial c.{id, name}';
        if (is_array($search) && !empty($search)) {
            $count = 1;
            $order = '(CASE';
            foreach ($search as $count => $id) {
                $order .= ' WHEN c.id = ' . $id . ' THEN ' . $count;
                $count++;
            }
            $order .= ' ELSE ' . $count . ' END) AS HIDDEN ORD';
            $select .= ", $order";
        }

        $q->select($select)
            ->from('MauticChatBundle:Channel', 'c', 'c.id')
            ->leftJoin('c.privateUsers', 'u');
        $q->where(
            $q->expr()->orX(
                $q->expr()->eq('c.isPrivate', ':false'),
                $q->expr()->andX(
                    $q->expr()->eq('c.isPrivate', ':true'),
                    $q->expr()->eq('u.id', ':userId')
                )
            )
        )
        ->setParameter('userId', $userId);

        $q->andWhere('c.isPublished = :true')
            ->setParameter(':true', true, 'boolean')
            ->setParameter(':false', false, 'boolean');

        if (!empty($search)) {
            if (is_array($search)) {
                $q->andWhere(
                    $q->expr()->in('c.id', ':channels')
                )->setParameter('channels', $search);
            } else {
                $q->andWhere(
                    $q->expr()->like('c.name', ':search')
                )->setParameter('search', "{$search}%");
            }
        }

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        if (!is_array($search)) {
            $q->orderBy('c.name', 'ASC');
        } elseif (!empty($search)) {
            //force array order
            $q->orderBy('ORD', 'ASC');
        }

        $query = $q->getQuery();
        $query->setHydrationMode(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $results = new Paginator($query);

        $iterator = $results->getIterator();

        $channels = array(
            'channels' => $iterator->getArrayCopy(),
            'count'    => count($iterator),
            'total'    => count($results)
        );

        return $channels;
    }

    /**
     * Retrieves array of names used to ensure unique name
     *
     * @param $excludingId
     * @return array
     */
    public function getNames($excludingId)
    {
        $q = $this->createQueryBuilder('c')
            ->select('c.name');
        if (!empty($exludingId)) {
            $q->where('c.id != :id')
                ->setParameter('id', $excludingId);
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
            ->update(MAUTIC_TABLE_PREFIX . 'chat_channels', array('is_published' => 0), array('id' => $channelId));
    }

    /**
     * @param $channelId
     */
    public function unarchiveChannel($channelId)
    {
        $this->_em->getConnection()
            ->update(MAUTIC_TABLE_PREFIX . 'chat_channels', array('is_published' => 1), array('id' => $channelId));
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
            $stat->setDateJoined($now->getDateTime());
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
    public function getChannelStatsForUser(User $user, Channel $channel = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('s, c')
            ->from('MauticChatBundle:ChannelStat', 's')
            ->join('s.channel', 'c')
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

        if ($channel == null) {
            $stats = array();
            foreach ($results as $r) {
                $stats[$r['channel']['id']] = $r;
            }
            return $stats;
        } else {
            return (count($results)) ? $results[0] : array();
        }
    }

    /**
     * @param       $userId
     * @param array $channels
     *
     * @return array
     */
    public function getUnreadForUser($userId)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('ch.id, count(c) as unread')
            ->from('MauticChatBundle:Channel', 'ch')
            ->leftJoin('ch.privateUsers', 'u')
            ->leftJoin('ch.chats', 'c')
            ->leftJoin('ch.stats', 's', 'WITH', 'IDENTITY(s.user) = :userId')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('c.fromUser'),
                        $qb->expr()->neq('IDENTITY(c.fromUser)', ':userId')
                    ),
                    $qb->expr()->gt('c.id', 's.lastRead')
                )
            )
            ->setParameter('userId', $userId);

        $qb->andWhere('ch.isPublished = :true')
            ->setParameter(':true', true, 'boolean');

        $qb->groupBy('ch.id');

        $results = $qb->getQuery()->getArrayResult();

        $unread = array();
        foreach ($results as $r) {
            $unread[$r['id']] = (int) $r['unread'];
        }

        return $unread;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'c';
    }

    /**
     * Get the last chat in the ID
     *
     * @param $channelId
     */
    public function getLastChatId($channelId)
    {
        $qb = $this->createQueryBuilder('ch');
        $qb->select('MAX(c.id) as lastId')
            ->join('MauticChatBundle:Chat', 'c', 'WITH', 'c.channel = ch')
            ->where('ch.id = ' . (int) $channelId);

        $result = $qb->getQuery()->getArrayResult();

        return (count($result)) ? $result[0]['lastId'] : 0;
    }

    /**
     * @param $channelId
     * @param $userId
     */
    public function deleteChannelStat($channelId, $userId)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete('MauticChatBundle:ChannelStat', 's')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('IDENTITY(s.channel)', ':channelId'),
                    $qb->expr()->eq('IDENTITY(s.user)', ':userId')
                )
            )
            ->setParameters(array(
                'channelId' => $channelId,
                'userId'    => $userId
            ));

        $qb->getQuery()->execute();
    }

    /**
     * Gets a list of messages that are unread for the user
     *
     * @param $userId
     * @param $includeNotified
     *
     * @return array
     */
    public function getUnreadMessages($userId, $includeNotified = false)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('partial c.{id, dateSent, message}, partial ch.{id, name, description}, partial fu.{id, username, firstName, lastName, email, lastActive}')
            ->from('MauticChatBundle:Chat', 'c', 'c.id')
            ->join('c.fromUser', 'fu')
            ->leftJoin('c.channel', 'ch')
            ->leftJoin('ch.privateUsers', 'u')
            ->leftJoin('ch.stats', 's', 'WITH', 'IDENTITY(s.user) = :userId')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('c.fromUser'),
                        $qb->expr()->neq('IDENTITY(c.fromUser)', ':userId')
                    ),
                    $qb->expr()->gt('c.id', 's.lastRead')
                )
            )
            ->setParameter('userId', (int) $userId);

        $qb->andWhere('ch.isPublished = :true')
            ->setParameter(':true', true, 'boolean');
        $qb->orderBy('c.dateSent', 'ASC');

        if (!$includeNotified) {
            $qb->andwhere(
                $qb->expr()->eq('c.isNotified', 0)
            );
        }

        $results = $qb->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * Search messages
     *
     * @param $filter
     * @param $userId
     * @param $limit
     *
     * @return array
     */
    public function getFilteredMessages($filter, $userId, $limit = 30)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('partial c.{id, dateSent, message}, partial ch.{id, name, description}, partial fu.{id, username, firstName, lastName, email, lastActive}')
            ->from('MauticChatBundle:Chat', 'c', 'c.id')
            ->join('c.fromUser', 'fu')
            ->leftJoin('c.channel', 'ch')
            ->leftJoin('ch.privateUsers', 'u')
            ->leftJoin('ch.stats', 's', 'WITH', 'IDENTITY(s.user) = :userId')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('c.fromUser'),
                        $qb->expr()->neq('IDENTITY(c.fromUser)', ':userId')
                    ),
                    $qb->expr()->like('c.message', ':filter')
                )
            )
            ->setParameter('userId', (int) $userId)
            ->setParameter('filter', '%' . $filter . '%');

        $qb->andWhere('ch.isPublished = :true')
            ->setParameter(':true', true, 'boolean');
        $qb->orderBy('c.dateSent', 'ASC');

        $qb->setMaxResults($limit);
        $query = $qb->getQuery();
        $query->setHydrationMode(constant("\\Doctrine\\ORM\\Query::HYDRATE_ARRAY"));

        $results = new Paginator($query);
        return $results;
    }
}