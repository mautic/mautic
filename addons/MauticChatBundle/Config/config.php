<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name'        => 'Chat',
    'description' => 'Enable chat communication between members.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes'      => array(
        'main' => array(
            'mautic_chat_index'         => array(
                'path'       => '/chat',
                'controller' => 'MauticChatBundle:Default:index'
            ),
            'mautic_chat_list'          => array(
                'path'       => '/chat/list/{page}',
                'controller' => 'MauticChatBundle:User:list'
            ),
            'mautic_chatchannel_list'   => array(
                'path'       => '/chat/channel/list/{page}',
                'controller' => 'MauticChatBundle:Channel:list'
            ),
            'mautic_chatchannel_action' => array(
                'path'       => '/chat/channel/{objectAction}/{objectId}',
                'controller' => 'MauticChatBundle:Channel:execute'
            ),
            'mautic_chat_action'        => array(
                'path'       => '/chat/{objectAction}/{objectId}',
                'controller' => 'MauticChatBundle:Default:execute'
            )
        )
    ),

    'services'    => array(
        'events' => array(
            'mautic.chat.subscriber'              => array(
                'class'     => 'MauticAddon\MauticChatBundle\EventListener\SidebarSubscriber'
            ),
            'mautic.chat.configbundle.subscriber' => array(
                'class'     => 'MauticAddon\MauticChatBundle\EventListener\ConfigSubscriber'
            ),
            'mautic.chat.search.subscriber'       => array(
                'class'     => 'MauticAddon\MauticChatBundle\EventListener\SearchSubscriber'
            )
        ),
        'forms'  => array(
            'mautic.form.type.chatchannel' => array(
                'class' => 'MauticAddon\MauticChatBundle\Form\Type\ChannelType',
                'alias' => 'chatchannel'
            ),
            'mautic.form.type.chatconfig'  => array(
                'class'     => 'MauticAddon\MauticChatBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',
                'alias'     => 'chatconfig'
            )
        )
    ),

    'parameters'  => array(
        'chat_notification_sound' => 'wet'
    )
);