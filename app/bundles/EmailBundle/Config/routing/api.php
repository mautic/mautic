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

$collection->add('mautic_api_getemails', new Route('/emails',
    array(
        '_controller' => 'MauticEmailBundle:Api\EmailApi:getEntities',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getemail', new Route('/emails/{id}',
    array(
        '_controller' => 'MauticEmailBundle:Api\EmailApi:getEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

return $collection;