<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Outlook',
    'description' => 'Enables integrations with Outlook for email tracking',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'services'    => [
        'integrations' => [
            'mautic.integration.outlook' => [
                'class'     => \MauticPlugin\MauticOutlookBundle\Integration\OutlookIntegration::class,
                'arguments' => [

                ],
            ],
        ],
    ],
];
