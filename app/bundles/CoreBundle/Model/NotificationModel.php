<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\UserBundle\Entity\User;

/**
 * Class NotificationModel
 */
class NotificationModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CoreBundle\Entity\AuditLogRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCoreBundle:Notification');
    }

    /**
     * Write a notification
     *
     * @param        $key       Key to ID the source of the notification
     * @param        $message   Message of the notification
     * @param        $isRead    Add unread indicator
     * @param        $header    Header for message (optional)
     * @param string $iconClass Font Awesome CSS class for the icon (e.g. fa-eye)
     * @param null   $user      User object; defaults to current user
     */
    public function insertNotification($key, $message, $isRead = false, $header = null, $iconClass = null, User $user = null)
    {
        if ($user == null) {
            $user = $this->factory->getUser();
        }

        $notification = new Notification();
        $notification->setKey($key);
        $notification->setIsRead($isRead);
        $notification->setHeader($header);
        $notification->setMessage($message);
        $notification->setIconClass($iconClass);
        $notification->setUser($user);
        $notification->setDateAdded(new \DateTime());
        $this->saveEntity($notification);
    }

    /**
     * @param null $key
     */
    public function getNotifications($key = null)
    {
        $filter = array(
            'force' => array(
                array(
                    'column' => 'n.user',
                    'expr'   => 'eq',
                    'value'  => $this->factory->getUser()
                )
            )
        );

        if ($key != null) {
            $filter['force'][] = array(
                'column' => 'n.key',
                'expr'   => 'eq',
                'value'  => $key
            );
        }

        $args = array(
            'filter' => $filter,
            'ignore_paginator' => true,
            'hydration_mode' => 'HYDRATE_ARRAY'
        );

        return $this->getEntities($args);
    }

    /**
     * Mark notifications read for a user
     */
    public function markAllRead()
    {
        $this->getRepository()->markAllReadForUser($this->factory->getUser()->getId());
    }

    /**
     * Clears a notification for a user
     *
     * @param $id Notification to clear; will clear all if empty
     */
    public function clearNotification($id)
    {
        $this->getRepository()->clearNotificationsForUser($this->factory->getUser()->getId(), $id);
    }
}
