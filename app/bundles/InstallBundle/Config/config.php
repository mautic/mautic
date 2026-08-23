<?php

declare(strict_types=1);

return [
    'routes' => [
        'public' => [
            'mautic_installer_remove_slash' => [
                'path'       => '/installer/',
                'controller' => 'Mautic\CoreBundle\Controller\CommonController::removeTrailingSlashAction',
            ],
        ],
    ],
];
