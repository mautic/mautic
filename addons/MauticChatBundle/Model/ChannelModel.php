<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\Model;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\UserBundle\Entity\User;
use MauticAddon\MauticChatBundle\Entity\Channel;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use MauticAddon\MauticChatBundle\Entity\ChannelStat;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ChannelModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class ChannelModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \MauticAddon\MauticChatBundle\Entity\ChannelRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticChatBundle:Channel');
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $usePreference
     */
    public function getMyChannels($search = '', $limit = 10, $start = 0, $usePreference = false)
    {
        static $channelListResults = array();

        $key = $search . $limit . $start . (int) $usePreference;

        if (!isset($channelListResults[$key])) {
            $repo = $this->getRepository();
            if ($usePreference) {
                $settings = $this->getSettings();

                //force user preferences
                $search = $settings['visible'];

                if (empty($search)) {
                    //prevent showing any channels until subscribed
                    $search[] = 0;
                }

                $limit = $start = 0;
            }

            $results = $repo->getUserChannels($this->factory->getUser(), $search, $limit, $start);
            $ids     = array_keys($results['channels']);

            //compare user preference with returned Ids and update if applicable
            if ($usePreference) {
                $diff = array_diff($settings['visible'], $ids);

                if (count($diff)) {
                    $settings['visible'] = $ids;
                    $this->setSettings($settings);
                }
            }

            $unread = $this->getChannelsWithUnreadMessages();

            //set the unread count
            $listedUnread = 0;
            $hasUnread    = array();
            foreach ($results['channels'] as $r) {
                if (!isset($unread[$r['id']])) {
                    $unread[$r['id']] = 0;
                } else {
                    $unread[$r['id']] = (int)$unread[$r['id']];
                }

                if ($unread[$r['id']] > 0) {
                    $hasUnread[] = $r['id'];
                }
                $listedUnread += $unread[$r['id']];
            }

            //total unread count
            $totalUnread       = array_sum($unread);
            $results['unread'] = array(
                'count'     => $totalUnread,
                'hidden'    => $totalUnread - $listedUnread,
                'channels'  => $unread,
                'hasUnread' => $hasUnread
            );

            $channelListResults[$key] = $results;
        }

        return $channelListResults[$key];
    }

    /**
     * Get channels with unread messages
     *
     * @param bool $includeIdList
     *
     * @return array
     */
    public function getChannelsWithUnreadMessages($includeIdList = false)
    {
        $repo = $this->getRepository();

        $unreadCounts = $repo->getUnreadForUser($this->factory->getUser()->getId());

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
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if ($entity == null) {
            $entity = new Channel();
        }

        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('chatchannel', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Channel();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (!$entity instanceof Channel) {
            throw new MethodNotAllowedHttpException(array('Channel'));
        }

        $isNew = ($entity->getId()) ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $name = $entity->getName();
        $name = strtolower(InputHelper::alphanum($name));

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testName  = $name;
        $names     = $repo->getNames($entity->getId());
        $count     = (int)in_array($testName, $names);
        $nameTag   = $count;

        while ($count) {
            $testAlias = $testName . $nameTag;
            $count     = (int)in_array($testAlias, $names);
            $nameTag++;
        }

        if ($testName != $name) {
            $name = $testName;
        }
        $entity->setName($name);

        $event = $this->dispatchEvent("pre_save", $entity, $isNew);
        $this->getRepository()->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew, $event);
    }

    /**
     * Archive the channel
     *
     * @param  $entity
     */
    public function archiveChannel($id)
    {
        $this->getRepository()->archiveChannel($id);
    }

    /**
     * Archive the channel
     *
     * @param  $entity
     */
    public function unarchiveChannel($id)
    {
        $this->getRepository()->unarchiveChannel($id);
    }

    /**
     * Get messages in chat
     *
     * @param User      $withUser
     * @param null      $lastId
     * @param \DateTime $fromDate
     *
     * @return mixed
     */
    public function getGroupMessages(Channel $channel, $lastId = null, \DateTime $fromDate = null)
    {
        if ($fromDate == null) {
            $fromDate  = $this->getChatHistoryDate($channel->getId());
        }
        return $this->getRepository()->getChannelConversation($channel, $lastId, $fromDate);
    }

    /**
     * @param int $channelId
     *
     * @return \Mautic\CoreBundle\Helper\DateTimeHelper
     */
    public function getChatHistoryDate($channelId)
    {
        //save the from date from history so that the user doesn't have to wait to scroll back again
        $session = $this->factory->getSession();

        $fromDate = $session->get('mautic.chatchannel.history.' . $channelId);
        if (empty($fromDate)) {
            //get today's chats only
            $fromDate = $this->factory->getDate(date('Y-m-d') . ' 00:00:00');
        } else {
            $fromDate = $this->factory->getDate(date('Y-m-d', strtotime($fromDate)) . ' 00:00:00');
        }
        $session->set('mautic.chatchannel.history.' . $channelId, $fromDate->toLocalString());

        return $fromDate->getUtcDateTime();
    }

    /**
     * @param Channel
     * @param int $lastId
     */
    public function markMessagesRead($channel, $lastId = 0)
    {
        $this->getRepository()->markRead($this->factory->getUser(), $channel, $lastId);
    }

    /**
     * @param Channel $channel
     *
     * @return array
     */
    public function getUserChannelStats(Channel $channel = null)
    {
        return $this->getRepository()->getChannelStatsForUser($this->factory->getUser(), $channel);
    }

    /**
     * Get channel settings
     *
     * @param $type
     *
     * @return mixed
     */
    public function getSettings($type = 'channels')
    {
        /** @var \MauticAddon\MauticChatBundle\Model\ChatModel $model */
        $model = $this->factory->getModel('addon.mauticChat.chat');
        return $model->getSettings($type);
    }

    /**
     * Set channel settings
     *
     * @param $typeSettings
     * @param $type
     */
    public function setSettings($typeSettings, $type = 'channels')
    {
        /** @var \MauticAddon\MauticChatBundle\Model\ChatModel $model */
        $model = $this->factory->getModel('addon.mauticChat.chat');
        return $model->setSettings($typeSettings, $type);
    }

    /**
     * Subscribe user to channel
     *
     * @param Channel $channel
     * @param User    $user
     */
    public function subscribeToChannel(Channel $channel, User $user = null)
    {
        if ($user == null) {
            $user = $this->factory->getUser();
        }

        $stat = $this->getUserChannelStats($channel);

        if (empty($stat)) {
            $now = new DateTimeHelper();

            //get the last read id
            $lastId = $this->getRepository()->getLastChatId($channel->getId());

            $stat = new ChannelStat();
            $stat->setChannel($channel);
            $stat->setUser($user);
            $stat->setLastRead($lastId);
            $stat->setDateRead($now->getDateTime());
            $stat->setDateJoined($now->getDateTime());
            $this->getRepository()->saveEntity($stat);
        }
    }

    /**
     * Unsubscribe user from channel
     *
     * @param Channel $channel
     * @param User    $user
     */
    public function unsubscribeFromChannel(Channel $channel, User $user = null)
    {
        if ($user == null) {
            $user = $this->factory->getUser();
        }

        $this->getRepository()->deleteChannelStat($channel->getId(), $user->getId());
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
     * @param $filter
     * @param $limit
     *
     * @return array
     */
    public function searchMessages($filter, $limit = 30)
    {
        $userId   = $this->factory->getUser()->getId();
        $messages = $this->getRepository()->getFilteredMessages($filter, $userId, $limit);

        $totalCount = count($messages);
        $messages   = $messages->getIterator()->getArrayCopy();

        return array(
            'messages' => $messages,
            'count'    => count($messages),
            'total'    => $totalCount
        );
    }
}
