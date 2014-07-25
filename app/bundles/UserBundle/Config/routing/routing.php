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

//authentication
$collection->add('login', new Route('/login', array(
    '_controller' => 'MauticUserBundle:Security:login',
)));

$collection->add('mautic_user_logincheck', new Route('/login_check', array()));
$collection->add('mautic_user_logout', new Route('/logout', array()));

//users
$collection->add('mautic_user_index', new Route('/users/{page}',
    array(
        '_controller' => 'MauticUserBundle:User:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_user_action', new Route('/users/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticUserBundle:User:execute',
        "objectId"      => 0
    )
));

//roles
$collection->add('mautic_role_index', new Route('/roles/{page}',
    array(
        '_controller' => 'MauticUserBundle:Role:index',
        'page'        => 1
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_role_action', new Route('/roles/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticUserBundle:Role:execute',
        "objectId"      => 0
    )
));

//account/profile
$collection->add('mautic_user_account', new Route('/account',
    array(
        '_controller' => 'MauticUserBundle:Profile:index'
    )
));

return $collection;