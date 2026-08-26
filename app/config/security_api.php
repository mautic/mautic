<?php

$container->loadFromExtension('fos_oauth_server', [
    'db_driver'           => 'orm',
    'client_class'        => Mautic\ApiBundle\Entity\oAuth2\Client::class,
    'access_token_class'  => Mautic\ApiBundle\Entity\oAuth2\AccessToken::class,
    'refresh_token_class' => Mautic\ApiBundle\Entity\oAuth2\RefreshToken::class,
    'auth_code_class'     => Mautic\ApiBundle\Entity\oAuth2\AuthCode::class,
    'service'             => [
        'user_provider' => Mautic\UserBundle\Security\Provider\UserProvider::class,
        'options'       => [
            // 'supported_scopes' => 'user'
            'access_token_lifetime'  => '%env(int:MAUTIC_API_OAUTH2_ACCESS_TOKEN_LIFETIME)%',
            'refresh_token_lifetime' => '%env(int:MAUTIC_API_OAUTH2_REFRESH_TOKEN_LIFETIME)%',
        ],
    ],
]);
