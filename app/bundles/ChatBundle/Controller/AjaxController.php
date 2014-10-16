<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChatBundle\Controller;

use Mautic\ChatBundle\Entity\Chat;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\FormBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * Initiates a chat between two users
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function startUserChatAction(Request $request, $userId = 0)
    {
        $dataArray   = array('success' => 0, 'ignore_wdt' => 1);

        $currentUser = $this->factory->getUser();
        $userId      = InputHelper::int($request->request->get('chatId', $userId));
        $userModel   = $this->factory->getModel('user.user');
        $user        = $userModel->getEntity($userId);

        if ($user instanceof User && $userId !== $currentUser->getId()) {
            $chatModel = $this->factory->getModel('chat.chat');
            $messages  = $chatModel->getDirectMessages($user);

            //get the HTML
            $dataArray['conversationHtml'] = $this->renderView('MauticChatBundle:User:index.html.php', array(
                'messages'            => $messages,
                'me'                  => $currentUser,
                'with'                => $user,
                'insertUnreadDivider' => true
            ));
            $dataArray['withName'] = $user->getName();
            $dataArray['withId']   = $user->getId();
            $lastActive = $user->getLastActive();
            if (!empty($lastActive)) {
                $dataArray['lastSeen'] = $lastActive->format(
                    $this->factory->getParameter('date_format_dateonly') . ' ' . $this->factory->getParameter('date_format_timeonly')
                );
            }
            $dataArray['mauticContent'] = 'chat';
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Initiates a channel chat
     *
     * @param Request $request
     * @param int     $channelId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function startChannelChatAction(Request $request, $channelId = 0)
    {
        $dataArray   = array('success' => 0, 'ignore_wdt' => 1);

        $currentUser = $this->factory->getUser();
        $channelId   = InputHelper::int($request->request->get('chatId', $channelId));
        $model       = $this->factory->getModel('chat.channel');
        $channel     = $model->getEntity($channelId);

        if ($channel !== null) {
            $messages  = $model->getGroupMessages($channel);
            $lastRead  = $model->getUserChannelStats($channel);

            //get the HTML
            $dataArray['conversationHtml'] = $this->renderView('MauticChatBundle:Channel:index.html.php', array(
                'messages' => $messages,
                'me'       => $currentUser,
                'channel'  => $channel
            ));
            $dataArray['channelId']   = $channel->getId();
            $dataArray['channelName'] = $this->renderview('MauticChatBundle:Channel:header.html.php', array(
                'channel' => $channel
            ));
            $dataArray['channelDesc'] = $channel->getDescription();
            if ($lastRead)  {
                $dataArray['lastReadId'] = $lastRead['lastRead'];
            }
            if ($messages) {
                $lastMsg = end($messages);
                $dataArray['latestId'] = $lastMsg['id'];
            }
            $dataArray['divider'] = $this->renderView('MauticChatBundle:User:newdivider.html.php', array('tag' => 'div'));;
            $dataArray['success'] = 1;
            $dataArray['mauticContent'] = 'chatChannel';
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Record a chat message
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function sendMessageAction(Request $request)
    {
        $dataArray   = array('success' => 0, 'ignore_wdt' => 1);

        $currentUser = $this->factory->getUser();
        $chatId      = InputHelper::int($request->request->get('chatId'));
        $chatType    = InputHelper::clean($request->request->get('chatType'));
        $message     = htmlspecialchars($request->request->get('msg'));

        if (!empty($message)) {
            //save the message
            $entity = new Chat();
            $entity->setDateSent(new \DateTime());
            $entity->setFromUser($currentUser);
            $entity->setMessage($message);

            if ($chatType == 'user') {
                $userModel = $this->factory->getModel('user.user');
                $recipient = $userModel->getEntity($chatId);
                if ($recipient !== null && $chatId !== $currentUser->getId()) {
                    $repo = $userModel->getRepository();
                    $entity->setToUser($recipient);
                }
            } elseif ($chatType == 'channel') {
                $channelModel = $this->factory->getModel('chat.channel');
                $recipient    = $channelModel->getEntity($chatId);
                if ($recipient !== null) {
                    $repo = $channelModel->getRepository();
                    $entity->setChannel($recipient);
                }
            }

            if ($recipient !== null) {
                $repo->saveEntity($entity);
                return $this->getMessageContent($request, $currentUser, $recipient, $chatType, 'send');
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Get new messages
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getMessagesAction(Request $request)
    {
        $dataArray   = array('success' => 0, 'ignore_wdt' => 1);

        $currentUser = $this->factory->getUser();
        $chatId      = InputHelper::int($request->request->get('chatId'));
        $chatType    = InputHelper::clean($request->request->get('chatType'));

        if ($chatType == 'user') {
            if ($chatId !== $currentUser->getId()) {
                $userModel = $this->factory->getModel('user.user');
                $recipient = $userModel->getEntity($chatId);
            } else {
                $recipient = null;
            }
        } elseif ($chatType == 'channel') {
            $channelModel = $this->factory->getModel('chat.channel');
            $recipient    = $channelModel->getEntity($chatId);
        }

        if ($recipient !== null) {
            return $this->getMessageContent($request, $currentUser, $recipient, $chatType, 'update');
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Mark messages as read
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function markReadAction (Request $request)
    {
        $dataArray   = array('success' => 0, 'ignore_wdt' => 1);

        $currentUser = $this->factory->getUser();
        $chatId      = InputHelper::int($request->request->get('chatId'));
        $chatType    = InputHelper::clean($request->request->get('chatType'));
        $lastId      = InputHelper::int($request->request->get('lastId'));

        if ($chatType == 'user') {
            if ($chatId !== $currentUser->getId()) {
                $userModel = $this->factory->getModel('user.user');
                $recipient = $userModel->getEntity($chatId);
                $chatModel = $this->factory->getModel('chat.chat');
                $chatModel->markMessagesRead($recipient, $lastId);
                $dataArray['success'] = 1;
            }
        } elseif ($chatType == 'channel') {
            $channelModel = $this->factory->getModel('chat.channel');
            $recipient    = $channelModel->getEntity($chatId);
            $channelModel->markMessagesRead($recipient, $lastId);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Update the channel and user lists
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateListAction(Request $request)
    {
        $response = $this->forward('MauticChatBundle:Default:index.html.php', array('ignoreAjax' => true));

        $dataArray = array(
            'newContent'    => $response->getContent(),
            'ignore_wdt'    => 1
        );

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     * @param         $currentUser
     * @param         $recipient
     * @param string  $fromAction
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function getMessageContent(Request $request, $currentUser, $recipient, $chatType, $fromAction ='')
    {
        $dataArray = array('ignore_wdt' => 1);
        $lastId    = InputHelper::int($request->request->get('lastId'));
        $groupId   = InputHelper::int($request->request->get('groupId'));

        $startId  = ($groupId && $groupId <= $lastId) ? $groupId - 1 : $lastId;

        if ($chatType == 'user') {
            $chatModel = $this->factory->getModel('chat.chat');
            $messages  = $chatModel->getDirectMessages($recipient, $startId);
        } else {
            $channelModel = $this->factory->getModel('chat.channel');
            $messages     = $channelModel->getGroupMessages($recipient, $startId);

            $lastRead = $channelModel->getUserChannelStats($recipient);
            if ($lastRead)  {
                $dataArray['lastReadId'] = $lastRead['lastRead'];
            }
        }

        //group started by; should be set unless something quirky happens with the HTML/JS/data
        $owner = ($groupId && isset($messages[$groupId])) ? $messages[$groupId]['fromUser']['id'] : 0;
        //find out if html should be appended to the group or new groups created
        $newGroup = $sameGroup = array();

        $groupCount = 0;
        foreach ($messages as $id => $msg) {
            if ($id > $lastId) {
                if (!isset($firstMessage)) {
                    $firstMessage = $msg;
                }

                if ($owner && $msg['fromUser']['id'] === $owner) {
                    //this should be part of the same group
                    $sameGroup[] = $this->renderView('MauticChatBundle:User:message.html.php', array('message' => $msg));
                } else {
                    //start or append to a new group
                    $owner                      = false;
                    $newGroup[$groupCount][$id] = $msg;

                    $next = next($messages);
                    if ($next && $next['fromUser']['id'] !== $msg['fromUser']['id']) {
                        $groupCount++;
                    }
                }
            }
        }

        if (!empty($sameGroup)) {
            $dataArray['appendToGroup'] = implode("\n", $sameGroup);
            $dataArray['groupId']       = $groupId;
        }

        if (!empty($newGroup)) {
            $groupHtml = "";

            //add a new message divider
            $divider = ($fromAction == 'update') ? $this->renderView('MauticChatBundle:User:newdivider.html.php') : '';

            foreach ($newGroup as $g) {
                if ($chatType == 'user') {
                    $groupHtml = $this->renderView('MauticChatBundle:User:messages.html.php', array(
                        'messages' => $g,
                        'me'       => $currentUser,
                        'with'     => $recipient
                    ));
                } else {
                    $groupHtml = $this->renderView('MauticChatBundle:Channel:messages.html.php', array(
                        'messages' => $g,
                        'me'       => $currentUser,
                        'channel'  => $recipient
                    ));
                }
            }

            $dataArray['messages'] = $groupHtml;
        }
        $dataArray['withId'] = $recipient->getId();
        $divider             = ($fromAction == 'update') ? $this->renderView('MauticChatBundle:User:newdivider.html.php',
            array('tag' => 'div')
        ) : '';
        $dataArray['divider'] = $divider;

        $lastMessage           = end($messages);
        $dataArray['latestId'] = $lastMessage['id'];
        $dataArray['firstId']  = (isset($firstMessage)) ? $firstMessage['id'] : 0;
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }
}