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

$collection->add('mautic_user_logincheck', new Route('/login_check', array()));
$collection->add('mautic_user_logout', new Route('/logout', array()));

$collection->add('mautic_user_index', new Route('/users/{page}',
    array(
        '_controller' => 'MauticUserBundle:Default:index',
        'page'        => 1
    ), array(
        'page' => '\d+',
    )
));

$collection->add('mautic_user_action', new Route('/users/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticUserBundle:Form:execute',
        "objectId"      => 0
    )
));

return $collection;