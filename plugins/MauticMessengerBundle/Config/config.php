<?php

return [
    'name'        => 'Messenger Integration',
    'description' => '',
    'author'      => 'kuzmany.biz',
    'version'     => '1.0.0',
    'routes'      => [
        'main' => [
            'mautic_messenger_index' => [
                'path'       => '/messenger/{page}',
                'controller' => 'MauticMessengerBundle:Messenger:index',
            ],
            'mautic_messenger_action' => [
                'path'       => '/messenger/{objectAction}/{objectId}',
                'controller' => 'MauticMessengerBundle:Messenger:execute',
            ],
        ],
        'public' => [
            'messenger_callback' => [
                'path'       => '/messenger/callback',
                'controller' => 'MauticMessengerBundle:Messenger:callback',
            ],
            'messenger_checkbox_plugin' => [
                'path'       => '/messenger/checkbox',
                'controller' => 'MauticMessengerBundle:Messenger:checkbox',
            ],
            'messenger_checkbox_plugin_js' => [
                    'path'   => '/messenger/checkbox.js',
                'controller' => 'MauticMessengerBundle:Messenger:checkboxJs',
            ],
        ],
    ],
    'services' => [
        'forms' => [
            'mautic.form.type.msg' => [
                'class'     => 'MauticPlugin\MauticMessengerBundle\Form\Type\MessengerMessageType',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
                'alias' => 'msg',
            ],
            'mautic.form.type.messenger.facebook' => [
                'class' => 'MauticPlugin\MauticMessengerBundle\Form\Type\MessengerType',
                'alias' => 'messenger_facebook',
            ],
            'mautic.form.type.messenger.checkbox' => [
                'class'     => 'MauticPlugin\MauticMessengerBundle\Form\Type\FormFieldMessengerCheckboxType',
                'alias'     => 'messenger_checkbox',
                'arguments' => [
                    'mautic.plugin.helper.messenger',
                ],
            ],
            'mautic.form.type.messenger.send_to_messenger' => [
                'class'     => 'MauticPlugin\MauticMessengerBundle\Form\Type\SendToMessengerType',
                'alias'     => 'messenger_send_to_messenger',
                'arguments' => ['mautic.messengerMessage.model.messengerMessage'],
            ],
        ],
        'events' => [
            'mautic.plugin.messenger.formbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticMessengerBundle\EventListener\FormSubscriber',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.plugin.messenger.campaignbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticMessengerBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.campaign.model.event',
                    'mautic.lead.model.lead',
                    'session',
                    'mautic.page.model.page',
                    'request_stack',
                    'doctrine.dbal.default_connection',
                    'mautic.helper.cookie',
                    'mautic.campaign.model.campaign',
                ],
            ],
        ],
        'helpers' => [
            'mautic.plugin.helper.messenger' => [
                'class'     => 'MauticPlugin\MauticMessengerBundle\Helper\MessengerHelper',
                'arguments' => [
                    'mautic.http.connector',
                    'request_stack',
                    'mautic.helper.core_parameters',
                    'mautic.lead.model.lead',
                    'mautic.helper.integration',
                    'mautic.helper.templating',
                ],
            ],
        ],
        'models' => [
            'mautic.messengerMessage.model.messengerMessage' => [
                'class' => 'MauticPlugin\MauticMessengerBundle\Model\MessengerMessageModel',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.plugin.messenger.messages' => [
                    'route'  => 'mautic_messenger_index',
                    'access' => ['messenger:messages:viewown', 'messenger:messages:viewother'],
                    'checks' => [
                        'integration' => [
                            'Messenger' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'parent'   => 'mautic.core.channels',
                    'priority' => 100,
                ],
            ],
        ],
    ],
    'parameters' => [],

];
