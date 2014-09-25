<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();


//oAuth 2
$collection->addCollection(
    $loader->import("@FOSOAuthServerBundle/Resources/config/routing/token.xml")
);

$collection->add('mautic_oauth2_server_auth_login', new Route('/oauth/v2/auth_login',
    array(
        '_controller' => 'MauticApiBundle:Security:oAuth2Login',
    )
));

$collection->add('mautic_oauth2_server_auth_login_check', new Route('/oauth/v2/auth_login_check',
    array(
        '_controller' => 'MauticApiBundle:Security:oAuth2LoginCheck',
    )
));

//Clients
$collection->add('mautic_client_index', new Route('/clients/{page}',
    array(
        '_controller' => 'MauticApiBundle:Client:index',
        'page'        => 1
    ),
    array(
        'page' => '\d+'
    )
));

$collection->add('mautic_client_action', new Route('/clients/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticApiBundle:Client:execute',
        "objectId"      => 0
    )
));

//oAuth 1.0a
$collection->addCollection(
    $loader->import("@BazingaOAuthServerBundle/Resources/config/routing/routing.yml")
);

$collection->add('mautic_oauth1_server_auth_login', new Route('/oauth/v1/auth_login',
    array(
        '_controller' => 'MauticApiBundle:Security:oAuth1Login',
    )
));

$collection->add('mautic_oauth1_server_auth_login_check', new Route('/oauth/v1/auth_login_check',
    array(
        '_controller' => 'MauticApiBundle:Security:oAuth1LoginCheck',
    )
));
//override bazinga's login allow route
$collection->add('bazinga_oauth_login_allow', new Route('/oauth/v1/auth_login/allow',
        array('_controller' => 'bazinga.oauth.controller.login:allowAction'),
        array('_method' => 'GET')
));

//Load bundle API urls
$apiRoute = $loader->import('mautic.api', 'mautic.api');
$apiRoute->addPrefix('/api');
$collection->addCollection($apiRoute);

return $collection;