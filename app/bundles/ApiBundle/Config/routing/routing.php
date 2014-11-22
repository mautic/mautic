<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

//oAuth 1.0a

//step one - get request token
$collection->add('bazinga_oauth_server_requesttoken', new Route('/oauth/v1/request_token',
    array('_controller' => 'bazinga.oauth.controller.server:requestTokenAction'),
    array('_method'     => 'GET|POST')
));

//step two - authenticate user and get authorization
$collection->add('bazinga_oauth_login_allow', new Route('/oauth/v1/authorize',
    array('_controller' => 'MauticApiBundle:OAuth1/Authorize:allow'),
    array('_method'     => 'GET')
));

$collection->add('bazinga_oauth_server_authorize', new Route('/oauth/v1/authorize',
    array('_controller' => 'bazinga.oauth.controller.server:authorizeAction'),
    array('_method'     => 'POST')
));

$collection->add('mautic_oauth1_server_auth_login', new Route('/oauth/v1/authorize_login',
    array('_controller' => 'MauticApiBundle:OAuth1/Security:login'),
    array('_method'     => 'GET|POST')
));

$collection->add('mautic_oauth1_server_auth_login_check', new Route('/oauth/v1/authorize_login_check',
    array('_controller' => 'MauticApiBundle:OAuth1/Security:loginCheck'),
    array('_method'     => 'GET|POST')
));

//step three - exchange request token for access token
$collection->add('bazinga_oauth_server_accesstoken', new Route('/oauth/v1/access_token',
    array('_controller' => 'bazinga.oauth.controller.server:accessTokenAction'),
    array('_method'     => 'GET|POST')
));

//oAuth 2

//step one - request access token
$collection->add('fos_oauth_server_token', new Route('/oauth/v2/token',
    array('_controller' => 'fos_oauth_server.controller.token:tokenAction'),
    array('_method'     => 'GET|POST')
));

//step two - authenticate user and get authorization
$collection->add('fos_oauth_server_authorize', new Route('/oauth/v2/authorize',
    array('_controller' => 'MauticApiBundle:OAuth2/Authorize:authorize'),
    array('_method'     => 'GET|POST')
));

$collection->add('mautic_oauth2_server_auth_login', new Route('/oauth/v2/authorize_login',
    array('_controller' => 'MauticApiBundle:OAuth2/Security:login'),
    array('_method'     => 'GET|POST')
));

$collection->add('mautic_oauth2_server_auth_login_check', new Route('/oauth/v2/authorize_login_check',
    array('_controller' => 'MauticApiBundle:OAuth2/Security:loginCheck'),
    array('_method'     => 'GET|POST')
));

//Clients
$collection->add('mautic_client_index', new Route('/credentials/{page}',
    array(
        '_controller' => 'MauticApiBundle:Client:index',
        'page'        => 1
    ),
    array(
        'page' => '\d+'
    )
));

$collection->add('mautic_client_action', new Route('/credentials/{objectAction}/{objectId}',
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