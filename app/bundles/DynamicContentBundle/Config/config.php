<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'menu' => [
        'main' => [
            'items' => [
                'mautic.dwc.list' => [
                    'route' => 'mautic_dwc_index',
                    'access' => ['dynamicContent:dynamicContents:viewown', 'dynamicContent:dynamicContents:viewother'],
                    'parent' => 'mautic.core.components',
                    'priority' => 200
                ]
            ]
        ]
    ],
    'routes' => [
        'main' => [
            'mautic_dwc_index' => [
                'path' => '/dwc/{page}',
                'controller' => 'MauticDynamicContentBundle:DynamicContent:index'
            ],
            'mautic_dwc_action' => [
                'path'       => '/dwc/{objectAction}/{objectId}',
                'controller' => 'MauticDynamicContentBundle:DynamicContent:execute'
            ]
        ],
        'api' => [

        ]
    ],
    'services' => [
        'events' => [
            'mautic.dwc.campaignbundle.subscriber' => [
                'class' => 'Mautic\DynamicContentBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.factory',
                    'mautic.lead.model.lead'
                ]
            ]
        ],
        'forms' => [
            'mautic.form.type.dwc'                       => [
                'class'     => 'Mautic\DynamicContentBundle\Form\Type\DynamicContentType',
                'arguments' => 'mautic.factory',
                'alias'     => 'dwc'
            ],
        ],
        'models' => [
            'mautic.dynamicContent.model.dynamicContent' => [
                'class' => 'Mautic\DynamicContentBundle\Model\DynamicContentModel',
                'arguments' => [
                    
                ]
            ]
        ]
    ]
];