<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

/**
 * NotificationRepository
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
        return array(
            array('n.dateAdded', 'DESC')
        );
    }

    /**
     * Mark user notifications as read
     *
     * @param $userId
     */
    public function markAllReadForUser($userId)
    {
        $this->_em->getConnection()->update(MAUTIC_TABLE_PREFIX . 'notifications', array('is_read' => 1), array('user_id' => (int) $userId));
    }

    /**
     * Clear notifications for a user
     *
     * @param      $userId
     * @param null $id      Clears all if empty
     *
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function clearNotificationsForUser($userId, $id = null)
    {
        $filter = array('user_id' => (int) $userId);

        if (!empty($id)) {
            $filter['id'] = (int) $id;
        }

        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX . 'notifications', $filter);
    }
}