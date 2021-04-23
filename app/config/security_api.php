<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//oAuth 2.0
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

//oAuth 1.0a
$container->loadFromExtension('bazinga_oauth_server', [
    'mapping' => [
        'db_driver'           => 'orm',
        'consumer_class'      => 'Mautic\ApiBundle\Entity\oAuth1\Consumer',
        'request_token_class' => 'Mautic\ApiBundle\Entity\oAuth1\RequestToken',
        'access_token_class'  => 'Mautic\ApiBundle\Entity\oAuth1\AccessToken',
    ],
    'service' => [
        'nonce_provider' => 'mautic.api.oauth1.nonce_provider',
    ],
]);
