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

$collection->add('mautic_api_getpoints', new Route('/points',
    array(
        '_controller' => 'MauticPointBundle:Api\PointApi:getEntities',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getpoint', new Route('/points/{id}',
    array(
        '_controller' => 'MauticPointBundle:Api\PointApi:getEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_gettriggers', new Route('/points/triggers',
    array(
        '_controller' => 'MauticPointBundle:Api\TriggerApi:getEntities',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_gettrigger', new Route('/points/triggers/{id}',
    array(
        '_controller' => 'MauticPointBundle:Api\TriggerApi:getEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

return $collection;