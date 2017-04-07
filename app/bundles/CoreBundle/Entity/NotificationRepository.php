<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

/**
 * NotificationRepository.
 */
class NotificationRepository extends CommonRepository
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'n';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getDefaultOrder()
    {
        return [
            ['n.dateAdded', 'DESC'],
        ];
    }

    /**
     * Mark user notifications as read.
     *
     * @param $userId
     */
    public function markAllReadForUser($userId)
    {
        $this->_em->getConnection()->update(MAUTIC_TABLE_PREFIX.'notifications', ['is_read' => 1], ['user_id' => (int) $userId]);
    }

    /**
     * Clear notifications for a user.
     *
     * @param      $userId
     * @param null $id     Clears all if empty
     * @param null $limit  Clears a set number
     *
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function clearNotificationsForUser($userId, $id = null, $limit = null)
    {
        if (!empty($id)) {
            $this->getEntityManager()->getConnection()->update(
                MAUTIC_TABLE_PREFIX.'notifications',
                [
                    'is_read' => 1,
                ],
                [
                    'user_id' => (int) $userId,
                    'id'      => $id,
                ]
            );
        } else {
            // Only mark the first 30 read
            $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $qb->update(MAUTIC_TABLE_PREFIX.'notifications')
                ->set('is_read', 1)
                ->where('user_id = '.(int) $userId.' AND is_read = 0')
                ->orderBy('id');

            if ($limit) {
                // Doctrine API doesn't support updates with limits
                $this->getEntityManager()->getConnection()->executeUpdate(
                    $qb->getSQL()." LIMIT $limit"
                );
            } else {
                $qb->execute();
            }
        }
    }

    /**
     * @return mixed|null
     */
    public function getUpstreamLastDate()
    {
        $qb = $this->createQueryBuilder('n')
            ->select('partial n.{id, dateAdded}')
            ->where('n.type = :type')
            ->setParameter('type', 'upstream')
            ->setMaxResults(1);

        /** @var Notification $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result === null ? null : $result->getDateAdded();
    }

    /**
     * Fetch notifications for this user.
     *
     * @param      $userId
     * @param null $afterId
     * @param bool $includeRead
     * @param null $type
     * @param null $limit
     *
     * @return array
     */
    public function getNotifications($userId, $afterId = null, $includeRead = false, $type = null, $limit = null)
    {
        $qb = $this->createQueryBuilder('n');

        $expr = $qb->expr()->andX(
            $qb->expr()->eq('IDENTITY(n.user)', (int) $userId)
        );

        if ($afterId) {
            $expr->add(
                $qb->expr()->gt('n.id', (int) $afterId)
            );
        }

        if (!$includeRead) {
            $expr->add(
                $qb->expr()->eq('n.isRead', 0)
            );
        }

        if (null !== $type) {
            $expr->add(
                $qb->expr()->eq('n.type', ':type')
            );
            $qb->setParameter('type', $type);
        }

        $qb->where($expr)
            ->orderBy('n.id');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
