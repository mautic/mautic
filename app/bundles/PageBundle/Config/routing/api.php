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

$collection->add('mautic_api_getpages', new Route('/pages',
    array(
        '_controller' => 'MauticPageBundle:Api\PageApi:getEntities',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getpage', new Route('/pages/{id}',
    array(
        '_controller' => 'MauticPageBundle:Api\PageApi:getEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

return $collection;