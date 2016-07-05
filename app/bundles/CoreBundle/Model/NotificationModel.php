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
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class NotificationModel
 */
class NotificationModel extends FormModel
{
    /**
     * @var boolean
     */
    protected $disableUpdates;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var UpdateHelper
     */
    protected $updateHelper;

    /**
     * NotificationModel constructor.
     *
     * @param PathsHelper $pathsHelper
     * @param UpdateHelper $updateHelper
     */
    public function __construct(PathsHelper $pathsHelper, UpdateHelper $updateHelper)
    {
        $this->pathsHelper = $pathsHelper;
        $this->updateHelper = $updateHelper;
    }

    /**
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param $disableUpdates
     */
    public function setDisableUpdates($disableUpdates)
    {
        $this->disableUpdates = $disableUpdates;
    }

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
     * @param           $message    Message of the notification
     * @param           $type       Optional $type to ID the source of the notification
     * @param bool|true $isRead     Add unread indicator
     * @param           $header     Header for message
     * @param string    $iconClass  Font Awesome CSS class for the icon (e.g. fa-eye)
     * @param \DateTime $datetime   Date the item was created
     * @param User|null $user       User object; defaults to current user
     */
    public function addNotification(
        $message,
        $type = null,
        $isRead = true,
        $header = null,
        $iconClass = null,
        \DateTime $datetime = null,
        User $user = null
    ) {
        if ($user === null) {
            $user = $this->user;
        }

        if ($user === null || !$user->getId()) {
            //ensure notifications aren't written for non users
            return;
        }

        $notification = new Notification();
        $notification->setType($type);
        $notification->setIsRead($isRead);
        $notification->setHeader(InputHelper::html($header));
        $notification->setMessage(InputHelper::html($message));
        $notification->setIconClass($iconClass);
        $notification->setUser($user);
        if ($datetime == null) {
            $datetime = new \DateTime();
        }
        $notification->setDateAdded($datetime);
        $this->saveEntity($notification);
    }

    /**
     * @param null $afterId
     * @param null $key
     *
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getNotifications($afterId = null, $key = null)
    {
        if ($this->user->getId()) {
            $filter = array(
                'force' => array(
                    array(
                        'column' => 'n.user',
                        'expr'   => 'eq',
                        'value'  => $this->user
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

            if ($afterId != null) {
                $filter['force'][] = array(
                    'column' => 'n.id',
                    'expr'   => 'gt',
                    'value'  => (int) $afterId
                );
            }

            $args = array(
                'filter'           => $filter,
                'ignore_paginator' => true,
                'hydration_mode'   => 'HYDRATE_ARRAY'
            );

            return $this->getEntities($args);
        }

        return array();
    }

    /**
     * Mark notifications read for a user
     */
    public function markAllRead()
    {
        $this->getRepository()->markAllReadForUser($this->user->getId());
    }

    /**
     * Clears a notification for a user
     *
     * @param $id Notification to clear; will clear all if empty
     */
    public function clearNotification($id)
    {
        $this->getRepository()->clearNotificationsForUser($this->user->getId(), $id);
    }

    /**
     * Get content for notifications
     *
     * @param null $afterId
     *
     * @return array
     */
    public function getNotificationContent($afterId = null)
    {
        if ($this->user->isGuest) {
            return array(array(), false, '');
        }

        $notifications = $this->getNotifications($afterId);

        $showNewIndicator = false;

        //determine if the new message indicator should be shown
        foreach ($notifications as $n) {
            if (!$n['isRead']) {
                $showNewIndicator = true;
                break;
            }
        }

        // Check for updates
        $updateMessage = '';
        $newUpdate     = false;

        if (!$this->disableUpdates && $this->user->isAdmin()) {
            $updateData = array();
            $cacheFile  = $this->pathsHelper->getSystemPath('cache').'/lastUpdateCheck.txt';

            //check to see when we last checked for an update
            $lastChecked = $this->session->get('mautic.update.checked', 0);

            if (time() - $lastChecked > 3600) {
                $this->session->set('mautic.update.checked', time());

                $updateData   = $this->updateHelper->fetchData();
            } elseif (file_exists($cacheFile)) {
                $updateData = json_decode(file_get_contents($cacheFile), true);
            }

            // If the version key is set, we have an update
            if (isset($updateData['version'])) {
                $announcement = $this->translator->trans(
                    'mautic.core.updater.update.announcement_link',
                    array('%announcement%' => $updateData['announcement'])
                );

                $updateMessage = $this->translator->trans(
                    $updateData['message'],
                    array('%version%' => $updateData['version'], '%announcement%' => $announcement)
                );

                $alreadyNotified = $this->session->get('mautic.update.notified');

                if (empty($alreadyNotified) || $alreadyNotified != $updateData['version']) {
                    $newUpdate = true;
                    $this->session->set('mautic.update.notified', $updateData['version']);
                }
            }
        }

        return array($notifications, $showNewIndicator, array('isNew' => $newUpdate, 'message' => $updateMessage));
    }
}