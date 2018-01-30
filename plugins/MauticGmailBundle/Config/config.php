<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Gmail',
    'description' => 'Enables integrations with Gmail for email tracking',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'services'    => [
        'integrations' => [
            'mautic.integration.gmail' => [
                'class'     => \MauticPlugin\MauticGmailBundle\Integration\GmailIntegration::class,
                'arguments' => [
                ],
            ],
        ],
    ],
];
