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
        $q->select('c, partial fu.{id, firstName, lastName, email, lastActive}, partial tu.{id, username, firstName, lastName, email, lastActive}')
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

        return $results;
    }

    /**
     * Returns a list of users that can be used in chat, etc
     *
     * @param int    $currentUserId
     * @param mixed  $search
     * @param int    $limit
     * @param int    $start
     */
    public function getUsers($currentUserId = 0, $search = '', $limit = 10, $start = 0)
    {
        $q = $this->_em->createQueryBuilder();

        $select = 'partial u.{id, username, firstName, lastName, email, lastActive, onlineStatus}';

        if (is_array($search) && !empty($search)) {
            $order = '(CASE';
            $count = 1;
            foreach ($search as $count => $id) {
                $order .= ' WHEN u.id = ' . $id . ' THEN ' . $count;
                $count++;
            }
            $order .= ' ELSE ' . $count . ' END) AS HIDDEN ORD';
            $select .= ", $order";
        }
        $q->select($select)
            ->from('MauticUserBundle:User', 'u', 'u.id');

        if (!empty($currentUserId)) {
            $q->where('u.id != :currentUser')
                ->setParameter('currentUser', $currentUserId);
        }

        $q->andWhere('u.isPublished = true');

        if (!empty($search)) {
            if (is_array($search)) {
                $q->andWhere(
                    $q->expr()->in('u.id', ':users')
                )->setParameter('users', $search);
            } else {
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
                )->setParameter('search', "{$search}%");
            }
        }

        if (!is_array($search)) {
            $q->orderBy('u.firstName, u.lastName');
        } elseif (!empty($search)) {
            //force array order
            $q->orderBy('ORD', 'ASC');
        }

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $query = $q->getQuery();
        $query->setHydrationMode(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $results = new Paginator($query);

        $iterator = $results->getIterator();

        $users = array(
            'users' => $iterator->getArrayCopy(),
            'count' => count($iterator),
            'total' => count($results)
        );

        return $users;
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
     * Mark messages as having been displayed to the user
     *
     * @param array $messageIds
     */
    public function markNotified(array $messageIds)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'chats')
            ->set('is_notified', 1)
            ->where($q->expr()->in('id', $messageIds))
            ->execute();
    }

    /**
     * @param       $toUser
     * @param array $fromUsers
     *
     * @return array
     */
    public function getUnreadMessageCount($toUser, array $fromUsers = array())
    {
        $q = $this->_em->createQueryBuilder();

        $expr = $q->expr()->andX(
            $q->expr()->eq('IDENTITY(c.toUser)', ':toUser'),
            $q->expr()->eq('c.isRead', 0)
        );

        if (!empty($fromUsers)) {
            $expr->add(
                $q->expr()->in('IDENTITY(c.fromUser)', ':fromUsers')
            );
            $q->setParameter('fromUsers', $fromUsers);
        }

        $q->select('count(c.id) as unread, u.id as userId')
            ->from('MauticChatBundle:Chat', 'c')
            ->leftJoin('c.fromUser', 'u')
            ->where($expr)
            ->setParameter('toUser', $toUser)
            ->groupBy('c.fromUser');
        $results = $q->getQuery()->getArrayResult();

        $return = array();
        foreach ($results as $r) {
            $return[$r['userId']] = $r['unread'];
        }

        return $return;
    }

    /**
     * Get all unread messages for a user
     *
     * @param $userId
     * @param $includeNotified
     *
     * @return array
     */
    public function getUnreadMessages($userId, $includeNotified = false)
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('partial c.{id, dateSent, message}, partial fu.{id, username, firstName, lastName, email, lastActive}')
            ->from('MauticChatBundle:Chat', 'c', 'c.id')
            ->join('c.fromUser', 'fu')
            ->join('c.toUser', 'tu')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('c.isRead', 0),
                    $q->expr()->eq('tu.id', ':userId')
                )
            )
            ->setParameter(':userId', (int) $userId)
            ->orderBy('c.dateSent', 'ASC');

        if (!$includeNotified) {
            $q->andwhere(
                $q->expr()->eq('c.isNotified', 0)
            );
        }

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * Searches messages
     *
     * @param $filter
     * @param $toUserId
     * @param $limit
     */
    public function getFilteredMessages($filter, $toUserId, $limit = 30)
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('partial c.{id, dateSent, message}, partial fu.{id, username, firstName, lastName, email, lastActive}')
            ->from('MauticChatBundle:Chat', 'c', 'c.id')
            ->join('c.fromUser', 'fu')
            ->join('c.toUser', 'tu')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('tu.id', ':userId'),
                    $q->expr()->like('c.message', ':filter')
                )
            )
            ->setParameter(':userId', (int) $toUserId)
            ->setParameter(':filter', '%' . $filter . '%')
            ->orderBy('c.dateSent', 'ASC');

        $q->setMaxResults($limit);
        $query = $q->getQuery();
        $query->setHydrationMode(constant("\\Doctrine\\ORM\\Query::HYDRATE_ARRAY"));

        $results = new Paginator($query);
        return $results;
    }

    /**
     * Deletes messages from and to a user
     *
     * @param $userId
     */
    public function deleteUserMessages($userId)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete('MauticChatBundle:Chat', 'c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('IDENTITY(c.fromUser)', ':id'),
                    $qb->expr()->eq('IDENTITY(c.toUser)', ':id')
                )
            )
            ->setParameter('id', (int) $userId);

        $qb->getQuery()->execute();
    }
}