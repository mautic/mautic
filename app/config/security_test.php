<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/** @var ContainerBuilder $container */
/** @var PhpFileLoader $loader */
$loader->import('security.php');

// Support HTTP basic auth for test logins
$container->loadFromExtension(
    'security',
    [
        'firewalls' => [
            'main' => [
                // Support HTTP basic auth for test logins
                'http_basic' => true,
            ],
        ],
        'password_hashers'  => [
            Symfony\Component\Security\Core\User\UserInterface::class => [
                'algorithm'        => 'md5',
                'encode_as_base64' => false,
                'iterations'       => 0,
            ],
            Mautic\UserBundle\Entity\User::class => [
                'algorithm'        => 'md5',
                'encode_as_base64' => false,
                'iterations'       => 0,
            ],
        ],
    ]
);
