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

$collection->add('mautic_api_getroles', new Route('/roles.{_format}',
    array(
        '_controller' => 'MauticApiBundle:User\Role:getEntities',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml'
    )
));


$collection->add('mautic_api_getrole', new Route('/roles/{id}.{_format}', array(
        '_controller' => 'MauticApiBundle:User\Role:getEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

return $collection;