<?php

declare(strict_types=1);

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\InstallBundle\Controller\InstallController;

return [
    'routes' => [
        'public' => [
            // InstallBundle routes stay on the config loader (not #[Route] attributes):
            // during installation the router is rebuilt under a tight memory limit, and
            // attribute scanning would autoload every controller and exhaust it.
            'mautic_installer_home' => [
                'path'       => '/installer',
                'controller' => InstallController::class.'::stepAction',
            ],
            'mautic_installer_step' => [
                'path'       => '/installer/step/{index}',
                'controller' => InstallController::class.'::stepAction',
            ],
            'mautic_installer_final' => [
                'path'       => '/installer/final',
                'controller' => InstallController::class.'::finalAction',
            ],
            'mautic_installer_remove_slash' => [
                'path'       => '/installer/',
                'controller' => CommonController::class.'::removeTrailingSlashAction',
            ],
            'mautic_installer_catchcall' => [
                'path'         => '/installer/{noerror}',
                'controller'   => InstallController::class.'::stepAction',
                'requirements' => [
                    'noerror' => '(?).+',
                ],
            ],
        ],
    ],
];
