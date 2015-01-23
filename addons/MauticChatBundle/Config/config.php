<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$config = array(
    'name'        => 'Chat',
    'description' => 'Enable chat communication between members.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes'      => array(
        'mautic_chat_index'         => array(
            'path'       => '/chat',
            'controller' => 'MauticChatBundle:Default:index'
        ),
        'mautic_chat_list'          => array(
            'path'       => '/chat/list/{page}',
            'controller' => 'MauticChatBundle:User:list',
            'defaults'   => array(
                'page' => 1
            )
        ),
        'mautic_chatchannel_list'   => array(
            'path'       => '/chat/channel/list/{page}',
            'controller' => 'MauticChatBundle:Channel:list',
            'defaults'   => array(
                'page' => 1
            )
        ),
        'mautic_chatchannel_action' => array(
            'path'       => '/chat/channel/{objectAction}/{objectId}',
            'controller' => 'MauticChatBundle:Channel:execute',
            'defaults'   => array(
                'objectId' => 0
            )
        ),
        'mautic_chat_action'        => array(
            'path'       => '/chat/{objectAction}/{objectId}',
            'controller' => 'MauticChatBundle:Default:execute',
            'defaults'   => array(
                'objectId' => 0
            )
        )
    ),

    'services'    => array(
        'events' => array(
            'mautic.chat.subscriber'              => array(
                'definition' => 'MauticAddon\MauticChatBundle\EventListener\SidebarSubscriber',
                'references' => 'mautic.factory'
            ),
            'mautic.chat.configbundle.subscriber' => array(
                'definition' => 'MauticAddon\MauticChatBundle\EventListener\ConfigSubscriber',
                'references' => 'mautic.factory'
            ),
            'mautic.chat.search.subscriber'       => array(
                'definition' => 'MauticAddon\MauticChatBundle\EventListener\SearchSubscriber',
                'references' => 'mautic.factory'
            )
        ),
        'forms'  => array(
            'mautic.form.type.chatchannel' => array(
                'definition' => 'MauticAddon\MauticChatBundle\Form\Type\ChannelType',
                'alias'      => 'chatchannel'
            ),
            'mautic.form.type.chatconfig'  => array(
                'definition' => 'MauticAddon\MauticChatBundle\Form\Type\ConfigType',
                'references' => 'mautic.factory',
                'alias'      => 'chatconfig'
            )
        )
    ),

    'parameters'  => array(
        'chat_notification_sound' => 'wet'
    )
);

return $config;