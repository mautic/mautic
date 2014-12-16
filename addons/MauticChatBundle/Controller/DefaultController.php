<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\Controller;

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
        $channelModel = $this->factory->getModel('addon.mauticChat.channel');
        $unsorted     = $channelModel->getMyChannels();

        $withUnread    = array();
        $withoutUnread = array();

        //let's sort by unread count then alphabetical
        foreach ($unsorted as $c) {
            if (!empty($c['stats']['unread'])) {
                $withUnread[] = $c;
            } else {
                $withoutUnread[] = $c;
            }
        }

        usort($withUnread, function($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });
        usort($withoutUnread, function($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });

        $channels = array_merge($withUnread, $withoutUnread);

        //get a list of  users
        $chatModel = $this->factory->getModel('addon.mauticChat.chat');
        $unsorted  = $chatModel->getUserList();

        $withUnread    = array();
        $withoutUnread = array();

        //let's sort by unread count then alphabetical
        foreach ($unsorted as $u) {
            if (!empty($u['stats']['unread'])) {
                $withUnread[] = $u;
            } else {
                $withoutUnread[] = $u;
            }
        }

        usort($withUnread, function($a, $b) {
            return strnatcasecmp($a['username'], $b['username']);
        });
        usort($withoutUnread, function($a, $b) {
            return strnatcasecmp($a['username'], $b['username']);
        });

        $users = array_merge($withUnread, $withoutUnread);

        $security = $this->factory->getSecurity();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'channels'    => $channels,
                'users'       => $users,
                'permissions' => $security->isGranted(array(
                    'addon:mauticChat:channels:create',
                    'addon:mauticChat:channels:editother',
                    'addon:mauticChat:channels:archiveother'
                ), 'RETURN_ARRAY'),
                'ignoreModal' => $this->request->get('ignoreModal', false)
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
                $chatModel = $this->factory->getModel('addon.mauticChat.chat');
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