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
        /** @var \MauticAddon\MauticChatBundle\Model\ChannelModel $channelModel */
        $channelModel = $this->factory->getModel('addon.mauticChat.channel');
        $channels     = $channelModel->getMyChannels(null, null, null, true);

        //get a list of  users
        /** @var \MauticAddon\MauticChatBundle\Model\ChatModel $chatModel */
        $chatModel = $this->factory->getModel('addon.mauticChat.chat');
        $users     = $chatModel->getUserList(null, null, null, true);

        $security  = $this->factory->getSecurity();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'channels'    => $channels,
                'users'       => $users,
                'permissions' => $security->isGranted(array(
                    'addon:mauticChat:channels:create'
                ), 'RETURN_ARRAY'),
                'ignoreModal' => $this->request->get('ignoreModal', false),
                'inPopup'     => $this->request->get('inPopup', false),
                'me'          => $this->factory->getUser(),
                'tmpl'        => $this->request->get('tmpl', 'index')
            ),
            'contentTemplate' => 'MauticChatBundle:Default:index.html.php',
            'passthroughVars' => array(
                'mauticContent'  => 'chat'
            )
        ));
    }
}