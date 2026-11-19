<?php

declare(strict_types=1);

return [
    'routes'   => [],
    'services' => [
        'menus' => [
            'mautic.menu.main' => [
                'alias' => 'main',
            ],
        ],
        'others' => [
            'mautic.some.helper' => [
                'class' => 'Mautic\CoreBundle\Helper\SomeHelper',
            ],
        ],
    ],
];
