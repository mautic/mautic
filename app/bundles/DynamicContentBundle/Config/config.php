<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'menu' => [
        'main' => [
            'items' => [
                'mautic.dynamicContent.dynamicContent' => [
                    'route' => 'mautic_dwc_index',
                    'access' => ['dynamicContent:dynamicContents:viewown', 'dynamicContent:dynamicContents:viewother'],
                    'parent' => 'mautic.core.components',
                    'priority' => 200,
                ],
            ],
        ],
    ],
    'routes' => [
        'main' => [
            'mautic_dwc_index' => [
                'path' => '/dwc/{page}',
                'controller' => 'MauticDynamicContentBundle:DynamicContent:index',
            ],
            'mautic_dwc_action' => [
                'path' => '/dwc/{objectAction}/{objectId}',
                'controller' => 'MauticDynamicContentBundle:DynamicContent:execute',
            ],
        ],
        'public' => [
            'mautic_dwc_generate_js' => [
                'path' => '/dwc/generate.js',
                'controller' => 'MauticDynamicContentBundle:Api\Js:generate'
            ],
            'mautic_api_dwc_index' => [
                'path' => '/dwc',
                'controller' => 'MauticDynamicContentBundle:Api\DynamicContentApi:getEntities'
            ],
            'mautic_api_dwc_action' => [
                'path' => '/dwc/{objectAlias}',
                'controller' => 'MauticDynamicContentBundle:Api\DynamicContentApi:process'
            ]
        ],
    ],
    'services' => [
        'events' => [
            'mautic.dwc.campaignbundle.subscriber' => [
                'class' => 'Mautic\DynamicContentBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.factory',
                    'mautic.lead.model.lead',
                    'mautic.dynamicContent.model.dynamicContent',
                    'session'
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.dwc' => [
                'class' => 'Mautic\DynamicContentBundle\Form\Type\DynamicContentType',
                'arguments' => [
                    'translator',
                    'mautic.security',
                    'mautic.dynamicContent.model.dynamicContent',
                    'request_stack',
                    'doctrine.orm.entity_manager',
                ],
                'alias' => 'dwc',
            ],
            'mautic.form.type.dwcsend_list' => [
                'class' => 'Mautic\DynamicContentBundle\Form\Type\DynamicContentSendType',
                'arguments' => [
                    'router',
                    'request_stack',
                ],
                'alias' => 'dwcsend_list',
            ],
            'mautic.form.type.dwcdecision_list' => [
                'class' => 'Mautic\DynamicContentBundle\Form\Type\DynamicContentDecisionType',
                'arguments' => [
                    'router',
                    'request_stack',
                ],
                'alias' => 'dwcdecision_list',
            ],
            'mautic.form.type.dwc_list' => [
                'class' => 'Mautic\DynamicContentBundle\Form\Type\DynamicContentListType',
                'arguments' => 'mautic.factory',
                'alias' => 'dwc_list',
            ],
        ],
        'models' => [
            'mautic.dynamicContent.model.dynamicContent' => [
                'class' => 'Mautic\DynamicContentBundle\Model\DynamicContentModel',
                'arguments' => [

                ],
            ],
        ],
    ],
];
