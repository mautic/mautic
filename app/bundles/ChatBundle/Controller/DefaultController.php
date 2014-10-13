<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChatBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\UserBundle\Entity\User;

/**
 * Class DefaultController
 *
 * @package Mautic\DefaultController\Controller
 */
class DefaultController extends FormController
{

    public function indexAction()
    {
        //get a list of channels
        $channelModel = $this->factory->getModel('chat.channel');
        $channels     = $channelModel->getMyChannels();

        //let's sort by unread count then alphabetical
        usort($channels, function($a, $b) {
            return $a['stats']['unread'] > $b['stats']['unread'];
        });

        //get a list of  users
        $chatModel = $this->factory->getModel('chat');
        $users     = $chatModel->getUserList();

        //sort by unread count
        usort($users, function($a, $b) {
            return $a['unread'] > $b['unread'];
        });

        return $this->delegateView(array(
            'viewParameters'  => array(
                'channels' => $channels,
                'users'    => $users
            ),
            'contentTemplate' => 'MauticChatBundle:Default:index.html.php',
            'passthroughVars' => array(
                'mauticContent'  => 'chat'
            )
        ));
    }

    public function dmAction($objectId = 0)
    {
        $chattingWith = (empty($objectId)) ? $this->factory->getSession()->get('mautic.chat.with', 0) : $objectId;
        if (!empty($chattingWith)) {
            $currentUser = $this->factory->getUser();
            $userModel   = $this->factory->getModel('user.user');
            $user        = $userModel->getEntity($chattingWith);

            if ($user instanceof User && $chattingWith !== $currentUser->getId()) {
                $chatModel = $this->factory->getModel('chat.chat');
                $messages  = $chatModel->getDirectMessages($user);

                //get the HTML
                return $this->delegateView(array(
                    'viewParameters'  => array(
                        'messages'            => $messages,
                        'me'                  => $currentUser,
                        'with'                => $user,
                        'insertUnreadDivider' => true
                    ),
                    'contentTemplate' => 'MauticChatBundle:User:index.html.php',
                    'passthroughVars' => array(
                        'mauticContent' => 'chat'
                    )
                ));
            }
        } else {
            //blank
            return $this->delegateView(array(
                'contentTemplate' => 'MauticChatBundle:User:index.html.php',
                'passthroughVars' => array(
                    'mauticContent' => 'chat'
                )
            ));
        }
    }
}