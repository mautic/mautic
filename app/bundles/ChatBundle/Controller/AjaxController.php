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

    protected function startChatAction(Request $request, $userId = 0)
    {
        $dataArray   = array('success' => 0, 'ignore_wdt' => 1);

        $currentUser = $this->factory->getUser();
        $userId      = InputHelper::int($request->request->get('user', $userId));
        $userModel   = $this->factory->getModel('user.user');
        $user        = $userModel->getEntity($userId);

        if ($user instanceof User && $userId !== $currentUser->getId()) {
            $chatModel = $this->factory->getModel('chat.chat');
            $messages  = $chatModel->getDirectMessages($user);

            //get the HTML
            $dataArray['conversationHtml'] = $this->renderView('MauticChatBundle:DM:index.html.php', array(
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

            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    protected function sendMessageAction(Request $request)
    {
        $dataArray   = array('success' => 0);

        $currentUser = $this->factory->getUser();
        $userId      = InputHelper::int($request->request->get('user'));
        $userModel   = $this->factory->getModel('user.user');
        $user        = $userModel->getEntity($userId);
        $message     = htmlspecialchars($request->request->get('msg'));

        if (!empty($message) && $user instanceof User && $userId !== $currentUser->getId()) {
            //save the message
            $entity = new Chat();
            $entity->setDateSent(new \DateTime());
            $entity->setFromUser($currentUser);
            $entity->setToUser($user);
            $entity->setMessage($message);
            $em = $this->factory->getEntityManager();
            $em->persist($entity);
            $em->flush();

            return $this->getMessageContent($request, $currentUser, $user, 'send');
        }

        return $this->sendJsonResponse($dataArray);
    }

    protected function getMessagesAction(Request $request)
    {
        $dataArray   = array('success' => 0);

        $currentUser = $this->factory->getUser();
        $userId      = InputHelper::int($request->request->get('user'));
        $userModel   = $this->factory->getModel('user.user');
        $user        = $userModel->getEntity($userId);

        if ($user instanceof User && $userId !== $currentUser->getId()) {
           return $this->getMessageContent($request, $currentUser, $user, 'update');
        }

        return $this->sendJsonResponse($dataArray);
    }

    protected function markReadAction (Request $request)
    {
        $dataArray   = array('success' => 0);

        $currentUser = $this->factory->getUser();
        $userId      = InputHelper::int($request->request->get('user'));
        $userModel   = $this->factory->getModel('user.user');
        $user        = $userModel->getEntity($userId);

        if ($user instanceof User && $userId !== $currentUser->getId()) {
            $lastId = InputHelper::int($request->request->get('lastId'));
            $chatModel = $this->factory->getModel('chat.chat');
            $chatModel->markMessagesRead($userId, $lastId);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function updateListAction(Request $request)
    {
        //get a list of channels
        $channelModel = $this->factory->getModel('chat.channel');
        $channels     = $channelModel->getMyChannels();

        $chatModel = $this->factory->getModel('chat.chat');
        //get a list of  users
        $users     = $chatModel->getUserList();

        $dataArray = array(
            'newContent' => $this->renderView('MauticChatBundle:Default:index.html.php', array(
                'channels'    => $channels,
                'users'       => $users,
                'contentOnly' => true
            )),
            'ignore_wdt' => 1
        );

        return $this->sendJsonResponse($dataArray);
    }

    private function getMessageContent(Request $request, $currentUser, $with, $fromAction ='')
    {
        $dataArray = array('ignore_wdt' => 1);
        $lastId    = InputHelper::int($request->request->get('lastId'));
        $groupId   = InputHelper::int($request->request->get('groupId'));
        $chatModel = $this->factory->getModel('chat.chat');

        $startId  = ($groupId && $groupId <= $lastId) ? $groupId - 1 : $lastId;
        $messages = $chatModel->getDirectMessages($with, $startId);

        //group started by; should be set unless something quirky happens with the HTML/JS/data
        $owner = ($groupId && isset($messages[$groupId])) ? $messages[$groupId]['fromUser']['id'] : 0;
        //find out if html should be appended to the group or new groups created
        $newGroup = $sameGroup = array();

        foreach ($messages as $id => $msg) {
            if ($id > $lastId) {
                if ($msg['fromUser']['id'] !== $owner) {
                    $newGroup[$id] = $msg;
                } else {
                    $sameGroup[] = $this->renderView('MauticChatBundle:DM:message.html.php', array('message' => $msg));
                }
            }
        }

        if (!empty($sameGroup)) {
            $divider = ($owner === $with->getId() && $fromAction == 'update') ? $this->renderView('MauticChatBundle:DM:newdivider.html.php',
                array('tag' => 'div')
            ) : '';
            $dataArray['appendToGroup'] = implode("\n", $sameGroup);
            $dataArray['groupId']       = $groupId;
        }

        if (!empty($newGroup)) {
            $groupHtml = $this->renderView('MauticChatBundle:DM:messages.html.php', array(
                'messages' => $newGroup,
                'me'       => $currentUser,
                'with'     => $with
            ));

            //add a new message divider
            $divider = ($fromAction == 'update') ? $this->renderView('MauticChatBundle:DM:newdivider.html.php') : '';

            $dataArray['messages'] = $groupHtml;
        }
        $dataArray['withId'] = $with->getId();
        if (!empty($divider)) {
            $dataArray['divider'] = $divider;
        }

        $lastMessage = end($messages);
        $dataArray['latestId'] = $lastMessage['id'];
        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }
}