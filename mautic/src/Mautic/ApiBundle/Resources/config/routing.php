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

//fos_oauth_server_token
$collection->addCollection(
    $loader->import("@FOSOAuthServerBundle/Resources/config/routing/token.xml")
);

//fos_oauth_server_authorize
$collection->addCollection(
    $loader->import("@FOSOAuthServerBundle/Resources/config/routing/authorize.xml")
);

$collection->add('mautic_oauth_server_auth_login', new Route('/oauth/v2/auth_login',
    array(
        '_controller' => 'MauticApiBundle:Security:login',
    )
));

$collection->add('mautic_oauth_server_auth_login_check', new Route('/oauth/v2/auth_login_check',
    array(
        '_controller' => 'MauticApiBundle:Security:loginCheck',
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

//Load bundle API urls
$apiRoute = $loader->import('mautic.api', 'mautic.api');
$apiRoute->addPrefix('/api');
$collection->addCollection($apiRoute);

return $collection;