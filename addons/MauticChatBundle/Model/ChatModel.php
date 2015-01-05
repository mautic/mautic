<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\UserBundle\Entity\User;

/**
 * Class ChatModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class ChatModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \MauticAddon\MauticChatBundle\Entity\ChatRepository
     */
    public function getRepository ()
    {
        return $this->em->getRepository('MauticChatBundle:Chat');
    }

    /**
     * Get direct messages between current user and passed in user
     *
     * @param User      $withUser
     * @param null      $lastId
     * @param \DateTime $fromDate
     *
     * @return mixed
     */
    public function getDirectMessages (User $withUser, $lastId = null, \DateTime $fromDate = null)
    {
        if ($fromDate == null) {
            $fromDate = $this->getChatHistoryDate($withUser);
        }

        return $this->getRepository()->getUserConversation($this->factory->getUser(), $withUser, $lastId, $fromDate);
    }

    /**
     * @param User $chattingWith
     *
     * @return \Mautic\CoreBundle\Helper\DateTimeHelper
     */
    public function getChatHistoryDate (User $chattingWith)
    {
        //save the from date from history so that the user doesn't have to wait to scroll back again
        $session = $this->factory->getSession();

        $fromDate = $session->get('mautic.chat.history.' . $chattingWith);
        if (empty($fromDate)) {
            //get today's chats only
            $fromDate = $this->factory->getDate(date('Y-m-d') . ' 00:00:00');
        } else {
            $fromDate = $this->factory->getDate(date('Y-m-d', strtotime($fromDate)) . ' 00:00:00');
        }
        $session->set('mautic.chat.history.' . $chattingWith, $fromDate->toLocalString());

        return $fromDate->getUtcDateTime();
    }

    /**
     * @param     $chattingWithId
     * @param int $lastId
     */
    public function markMessagesRead ($chattingWithId, $lastId = 0)
    {
        $this->getRepository()->markRead($this->factory->getUser()->getId(), $chattingWithId, $lastId);
    }

    /**
     * Get a list of users for chat
     *
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $usePreference
     *
     * @return array
     */
    public function getUserList ($search = '', $limit = 10, $start = 0, $usePreference = false)
    {
        static $userListResults = array();

        $key = $search . $limit . $start . (int) $usePreference;

        if (!isset($userListResults[$key])) {
            $repo = $this->getRepository();

            if ($usePreference) {
                $settings = $this->getSettings();
                $count    = count($settings['visible']);

                if ($count) {
                    //force user preferences
                    $search = $settings['visible'];
                    $limit  = $start = 0;
                }
            }

            $results = $repo->getUsers($this->factory->getUser()->getId(), $search, $limit, $start);

            if ($usePreference && isset($settings['cleanSlate'])) {
                $settings['visible'] = array_keys($results['users']);
                $this->setSettings($settings);
            }

            list($unread, $hasUnread) = $this->getUnreadCounts(true);

            //set the unread count
            $listedUnread = 0;
            foreach ($results['users'] as $r) {
                if (!isset($unread[$r['id']])) {
                    $unread[$r['id']] = 0;
                } else {
                    $unread[$r['id']] = (int)$unread[$r['id']];
                }

                $listedUnread += $unread[$r['id']];
            }

            //total unread count
            $totalUnread       = array_sum($unread);
            $results['unread'] = array(
                'count'     => $totalUnread,
                'hidden'    => $totalUnread - $listedUnread,
                'users'     => $unread,
                'hasUnread' => $hasUnread
            );

            $userListResults[$key] = $results;
        }

        return $userListResults[$key];
    }

    /**
     * @return array
     */
    public function getUnreadCounts($includeIdList = false)
    {
        $unreadCounts = $this->getRepository()->getUnreadMessageCount($this->factory->getUser()->getId());

        if ($includeIdList) {
            $hasUnread = array();
            foreach ($unreadCounts as $id => $count) {
                if ($count > 0) {
                    $hasUnread[] = $id;
                }
            }

            return array($unreadCounts, $hasUnread);
        } else {
            return $unreadCounts;
        }
    }

    /**
     * Get chat settings
     *
     * @param $type
     *
     * @return mixed
     */
    public function getSettings ($type = 'users')
    {
        /** @var \Mautic\UserBundle\Model\UserModel $model */
        $model    = $this->factory->getModel('user');
        $settings = $model->getPreference('mauticChat.settings', array(
            'users'    => array(
                'cleanSlate' => true,
                'visible' => array(),
                'silent'  => array(),
                'mute'    => array()
            ),
            'channels' => array(
                'cleanSlate' => true,
                'visible' => array(),
                'silent'  => array(),
                'mute'    => array()
            )
        ));

        return ($type == null) ? $settings : $settings[$type];
    }

    /**
     * Set chat settings
     *
     * @param $typeSettings
     * @param $type
     */
    public function setSettings ($typeSettings, $type = 'users')
    {
        /** @var \Mautic\UserBundle\Model\UserModel $model */
        $model    = $this->factory->getModel('user');
        $settings = $model->getPreference('mauticChat.settings', array(
            'users'    => array(
                'cleanSlate' => true,
                'visible' => array(),
                'silent'  => array(),
                'mute'    => array()
            ),
            'channels' => array(
                'cleanSlate' => true,
                'visible' => array(),
                'silent'  => array(),
                'mute'    => array()
            )
        ));

        if (isset($typeSettings['cleanSlate'])) {
            unset($typeSettings['cleanSlate']);
        }

        $settings[$type] = $typeSettings;
        $model->setPreference('mauticChat.settings', $settings);
    }

    /**
     * Get a list of unread messages
     *
     * @param $includeNotified
     *
     * @return array
     */
    public function getNewUnreadMessages($includeNotified = false)
    {
        $user = $this->factory->getUser();

        return $this->getRepository()->getUnreadMessages($user->getId(), $includeNotified);
    }

    /**
     * Marks an array of message ids as the user was notified
     *
     * @param array $messageIds
     *
     * @return mixed
     */
    public function markMessagesNotified(array $messageIds)
    {
        return $this->getRepository()->markNotified($messageIds);
    }
}