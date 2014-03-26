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

$collection->add('login', new Route('/login', array(
    '_controller' => 'MauticUserBundle:Security:login',
)));

$collection->add('login_check', new Route('/login_check', array()));
$collection->add('logout', new Route('/logout', array()));

//Because /users/{page} has an optional param, /users/ will now not work so have to specify it specifically
$collection->add('mautic_user_index', new Route('/users/', array(
    '_controller' => 'MauticUserBundle:Default:index'
)));

$collection->add('mautic_user_index', new Route('/users/{page}',
    array(
        '_controller' => 'MauticUserBundle:Default:index',
        'page'        => 1
    ), array(
        'page' => '\d+',
    )
));

$collection->add('mautic_user_new', new Route('/users/new', array(
    '_controller' => 'MauticUserBundle:Form:new'
)));

$collection->add('mautic_user_edit', new Route('/users/edit/{userId}', array(
    '_controller' => 'MauticUserBundle:Form:edit'
)));

return $collection;