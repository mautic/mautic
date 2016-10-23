<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Citrix',
    'description' => 'Enables integration with Mautic supported Citrix collaboration products.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'=>[
        'public'=>[
            'mautic_citrix_proxy' => [
                'path'         => '/citrix/proxy',
                'controller'   => 'MauticCitrixBundle:Public:proxy',
            ],
            'mautic_citrix_sessionchanged' => [
                'path'         => '/citrix/sessionChanged',
                'controller'   => 'MauticCitrixBundle:Public:sessionChanged',
            ],
        ],
    ],
    'services'    => [
        'events' => [
            'mautic.citrix.formbundle.subscriber' => [
                'class' => 'MauticPlugin\MauticCitrixBundle\EventListener\FormSubscriber',
            ],
        ],
        'forms'  => [
            'mautic.form.type.fieldslist.citrixlist'  => [
                'class' => 'MauticPlugin\MauticCitrixBundle\Form\Type\CitrixListType',
                'alias' => 'citrix_list',
            ],
            'mautic.form.type.citrix.submitaction'  => [
                'class' => 'MauticPlugin\MauticCitrixBundle\Form\Type\CitrixActionType',
                'alias' => 'citrix_submit_action',
            ],
        ],
    ],
];
