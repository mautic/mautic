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

$collection->add('mautic_api_getusers', new Route('/users.{_format}',
    array(
        '_controller' => 'MauticApiBundle:User\User:getEntities',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml'
    )
));

$collection->add('mautic_api_newuser', new Route('/users/new.{_format}',
    array(
        '_controller' => 'MauticApiBundle:User\User:newEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'POST',
        '_format' => 'json|xml'
    )
));

$collection->add('mautic_api_getuser', new Route('/users/{id}.{_format}',
    array(
        '_controller' => 'MauticApiBundle:User\User:getEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_editputuser', new Route('/users/{id}/edit.{_format}',
    array(
        '_controller' => 'MauticApiBundle:User\User:editEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'PUT',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_editpatchuser', new Route('/users/{id}/edit.{_format}',
    array(
        '_controller' => 'MauticApiBundle:User\User:editEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'PATCH',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_deleteuser', new Route('/users/{id}/delete.{_format}',
    array(
        '_controller' => 'MauticApiBundle:User\User:deleteEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'DELETE',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_checkpermission', new Route('/users/{id}/permissioncheck.{_format}',
    array(
        '_controller' => 'MauticApiBundle:User\User:isGranted',
        '_format' => 'json'
    ),
    array(
        '_method' => 'POST',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));


return $collection;