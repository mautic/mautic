<?php

$container->loadFromExtension('fos_oauth_server', [
    'db_driver'           => 'orm',
    'client_class'        => 'Mautic\ApiBundle\Entity\oAuth2\Client',
    'access_token_class'  => 'Mautic\ApiBundle\Entity\oAuth2\AccessToken',
    'refresh_token_class' => 'Mautic\ApiBundle\Entity\oAuth2\RefreshToken',
    'auth_code_class'     => 'Mautic\ApiBundle\Entity\oAuth2\AuthCode',
    'service'             => [
        'user_provider' => 'mautic.user.provider',
        'options'       => [
            //'supported_scopes' => 'user'
            'access_token_lifetime'  => '%env(int:MAUTIC_API_OAUTH2_ACCESS_TOKEN_LIFETIME)%',
            'refresh_token_lifetime' => '%env(int:MAUTIC_API_OAUTH2_REFRESH_TOKEN_LIFETIME)%',
        ],
    ],
]);
