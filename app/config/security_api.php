<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//oAuth 2.0
$container->loadFromExtension('fos_oauth_server', array(
    'db_driver'           => 'orm',
    'client_class'        => 'Mautic\ApiBundle\Entity\oAuth2\Client',
    'access_token_class'  => 'Mautic\ApiBundle\Entity\oAuth2\AccessToken',
    'refresh_token_class' => 'Mautic\ApiBundle\Entity\oAuth2\RefreshToken',
    'auth_code_class'     => 'Mautic\ApiBundle\Entity\oAuth2\AuthCode',
    'service'             => array(
        'user_provider' => 'mautic.user.provider',
        'options'       => array(
            'supported_scopes' => 'user'
        )
    ),
    'template'            => array(
        'engine' => 'php'
    )
));

//oAuth 1.0a
$container->loadFromExtension('bazinga_oauth_server', array(
    'mapping' => array(
        'db_driver'           => 'orm',
        'consumer_class'      => 'Mautic\ApiBundle\Entity\oAuth1\Consumer',
        'request_token_class' => 'Mautic\ApiBundle\Entity\oAuth1\RequestToken',
        'access_token_class'  => 'Mautic\ApiBundle\Entity\oAuth1\AccessToken'
    ),
    'service' => array(
        'nonce_provider'    => 'mautic.api.oauth1.nonce_provider'
    )
));