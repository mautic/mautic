<?php
/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://Mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Mautic DNC Event',
    'description' => 'Create a campaign event do not contact.',
    'version'     => '1.0',
    'author'      => 'Rogério Maciel e Alan Mosko',

    'services'    => [
        //Models Definition
        'models' => [
            'mautic.dncevent.model.model' => [
                'class' => 'MauticPlugin\MauticDNCEventBundle\Model\DNCEventModel',
                'alias' => 'dncevent.model',
            ],
        ],

        'forms'  => [
            'mautic.form.type.dnceventaddform_type' => [
                'class'     => 'MauticPlugin\MauticDNCEventBundle\Form\Type\FormDNCAddType',
                'arguments' => [
                    'mautic.model.factory',
                    'router',
                    'mautic.dncevent.model.model',
                ],
                'alias'     => 'dncevent_add_type_form',
            ],
            'mautic.form.type.dnceventremoveform_type' => [
                'class'     => 'MauticPlugin\MauticDNCEventBundle\Form\Type\FormDNCRemoveType',
                'arguments' => [
                    'mautic.model.factory',
                    'router',
                    'mautic.dncevent.model.model',
                ],
                'alias'     => 'dncevent_remove_type_form',
            ],
        ],

        'events' => [
            'mautic.dncevent.event.addDnc'    => [
                'class'     => 'MauticPlugin\MauticDNCEventBundle\Events\DNCEvent',
                'arguments' => 'Mautic.factory',
            ],
            'mautic.dncevent.event.removeDnc' => [
                'class'     => 'MauticPlugin\MauticDNCEventBundle\Events\DNCEventRemove',
                'arguments' => 'Mautic.factory',
            ],
        ],
    ],
];
