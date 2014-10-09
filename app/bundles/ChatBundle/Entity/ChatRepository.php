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
/**
 * ChatRepository
 */
class ChatRepository extends CommonRepository
{
    /**
     * Gets a conversation between two users
     *
     * @param $currentUser
     * @param $withUser
     * @param $lastId
     * @param $fromDateTime
     *
     * @return array
     */
    public function getUserConversation($currentUser, $withUser, $lastId = null, \DateTime $fromDateTime = null)
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('c, partial fu.{id, firstName, lastName, email}, partial tu.{id, firstName, lastName, email}')
            ->from('MauticChatBundle:Chat', 'c', 'c.id')
            ->join('c.fromUser', 'fu')
            ->join('c.toUser', 'tu')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('fu.id', ':currentUser'),
                    $q->expr()->eq('tu.id', ':withUser')
                )
            )
            ->orWhere(
                $q->expr()->andX(
                    $q->expr()->eq('fu.id', ':withUser'),
                    $q->expr()->eq('tu.id', ':currentUser')
                )

            )
            ->setParameter(':currentUser', $currentUser->getId())
            ->setParameter(':withUser', $withUser->getId())
            ->orderBy('c.dateSent', 'ASC');

        if ($lastId !== null) {
            $q->andWhere(
                $q->expr()->gt('c.id', ':lastId')
            )->setParameter(':lastId', $lastId);
        } elseif ($fromDateTime !== null) {
            $q->andWhere(
                $q->expr()->gte('c.dateSent', ':fromDate')
            )->setParameter(':fromDate', $fromDateTime);
        }

        $results = $q->getQuery()->getArrayResult();

        $this->generateOnlineStatuses($results, array('fromUser', 'toUser'));

        return $results;
    }

    /**
     * Returns a list of users that can be used in chat, etc
     *
     * @param int    $currentUserId
     * @param string $search
     * @param int    $limit
     * @param int    $start
     */
    public function getUsers($currentUserId = 0, $search = '', $limit = 10, $start = 0)
    {
        $q = $this->_em->createQueryBuilder();

        $q->select('u.id, u.firstName, u.lastName, u.email, u.lastActive, u.onlineStatus')
            ->from('MauticUserBundle:User', 'u', 'u.id');
        if (!empty($currentUserId)) {
            $q->where('u.id != :currentUser')
                ->setParameter('currentUser', $currentUserId);
        }

        $q->orderBy('u.firstName, u.lastName');

        if (!empty($search)) {
            $q->andWhere(
                $q->expr()->orX(
                    $q->expr()->like('u.email', ':search'),
                    $q->expr()->like('u.firstName', ':search'),
                    $q->expr()->like('u.lastName', ':search'),
                    $q->expr()->like(
                        $q->expr()->concat('u.firstName',
                            $q->expr()->concat(
                                $q->expr()->literal(' '),
                                'u.lastName'
                            )
                        ),
                        ':search'
                    )
                )
            )
                ->setParameter('search', "{$search}%");
        }

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $results = $q->getQuery()->getArrayResult();

        $this->generateOnlineStatuses($results);

        return $results;
    }

    private function generateOnlineStatuses(&$results, $key = null)
    {
        //fix online statuses
        $dt    = new DateTimeHelper(strtotime('15 minutes ago'), 'U', 'local');
        $delay = $dt->getUtcDateTime();
        foreach ($results as &$r) {
            if (is_array($key)) {
                foreach ($key as $k) {
                    if ($k === null) {
                        $use =& $r;
                    } else {
                        $use =& $r[$k];
                    }

                    if (empty($use['onlineStatus'])) {
                        $use['onlineStatus'] = ($use['lastActive'] >= $delay) ? 'online' : 'offline';
                    } elseif (!empty($use['onlineStatus']) && $use['lastActive'] < $delay) {
                        $use['onlineStatus'] = 'offline';
                    }
                    unset($use);
                }
            } else {
                if ($key === null) {
                    $use =& $r;
                } else {
                    $use =& $r[$key];
                }

                if (empty($use['onlineStatus'])) {
                    $use['onlineStatus'] = ($use['lastActive'] >= $delay) ? 'online' : 'offline';
                } elseif (!empty($use['onlineStatus']) && $use['lastActive'] < $delay) {
                    $use['onlineStatus'] = 'offline';
                }
                unset($use);
            }
        }
    }

    /**
     * Marks messages as read
     *
     * @param      $toUserId
     * @param      $fromUserId
     * @param null $upToId
     */
    public function markRead($toUserId, $fromUserId, $upToId = 0)
    {
        $now = new DateTimeHelper();
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'chats')
            ->set('is_read', 1)
            ->set('date_read', ':readDate')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('from_user', ':from'),
                    $q->expr()->eq('to_user', ':to'),
                    $q->expr()->lte('id', ':id')
                )
            )
            ->setParameter('readDate', $now->toUtcString())
            ->setParameter('from', $fromUserId)
            ->setParameter('to', $toUserId)
            ->setParameter('id', $upToId)
            ->execute();
    }

    /**
     * Returns a list of active users
     *
     * @param int    $currentUserId
     * @param string $search
     * @param int    $limit
     * @param int    $start
     */
    public function getActiveUsers($currentUserId, $search = '', $limit = 10, $start = 0)
    {
        $q = $this->_em->createQueryBuilder();

        //consider a user active if their last activity is within 3 minute ago
        $dt = new DateTimeHelper(strtotime('3 minutes ago'), 'U', 'local');
        $delay = $dt->getUtcDateTime();

        $q->select('partial u.{id, firstName, lastName, email, lastActive, onlineStatus}')
            ->from('MauticUserBundle:User', 'u')
            ->where('u.lastActive >= :delay')
            ->setParameter('delay', $delay)
            ->andWhere('u.id != :currentUser')
            ->setParameter('currentUser', $currentUserId)
            ->orderBy('u.firstName, u.lastName');

        if (!empty($search)) {
            $q->andWhere(
                $q->expr()->orX(
                    $q->expr()->like('u.email', ':search'),
                    $q->expr()->like('u.firstName', ':search'),
                    $q->expr()->like('u.lastName', ':search'),
                    $q->expr()->like(
                        $q->expr()->concat('u.firstName',
                            $q->expr()->concat(
                                $q->expr()->literal(' '),
                                'u.lastName'
                            )
                        ),
                        ':search'
                    )
                )
            )
                ->setParameter('search', "{$search}%");
        }

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $results = $q->getQuery()->getArrayResult();
        return $results;
    }

    public function getUnreadMessageCount($toUser, array $fromUsers)
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('c.id, count(c.id) as unread, u.id as userId')
            ->from('MauticChatBundle:Chat', 'c')
            ->leftJoin('c.fromUser', 'u')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('IDENTITY(c.toUser)', ':toUser'),
                    $q->expr()->in('IDENTITY(c.fromUser)', ':fromUsers'),
                    $q->expr()->eq('c.isRead', 0)
                )
            )
            ->setParameter('toUser', $toUser)
            ->setParameter('fromUsers', $fromUsers)
            ->groupBy('c.fromUser');
        $results = $q->getQuery()->getArrayResult();
        return $results;
    }

}
