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

$collection->add('mautic_api_getusers', new Route('/users',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:getEntities',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getuser', new Route('/users/{id}',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:getEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_getself', new Route('/users/self',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:getSelf',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

/*
$collection->add('mautic_api_newuser', new Route('/users/new',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:newEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'POST',
    )
));

$collection->add('mautic_api_editputuser', new Route('/users/{id}/edit',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:editEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'PUT',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_editpatchuser', new Route('/users/{id}/edit',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:editEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'PATCH',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_deleteuser', new Route('/users/{id}/delete',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:deleteEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'DELETE',
        'id'      => '\d+'
    )
));
*/

$collection->add('mautic_api_checkpermission', new Route('/users/{id}/permissioncheck',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:isGranted',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'POST',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_getuserroles', new Route('/users/list/roles',
    array(
        '_controller' => 'MauticUserBundle:Api\UserApi:getRoles',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getroles', new Route('/roles',
    array(
        '_controller' => 'MauticUserBundle:Api\RoleApi:getEntities',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getrole', new Route('/roles/{id}', array(
        '_controller' => 'MauticUserBundle:Api\RoleApi:getEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

return $collection;