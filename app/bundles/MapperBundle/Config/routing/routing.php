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

$collection->add('mautic_mapper_index', new Route('/mapper/dashboard',
    array('_controller' => 'MauticMapperBundle:Dashboard:index')
));

$collection->add('mautic_mapper_client_index', new Route('/mapper/client/{application}/{page}',
    array(
        '_controller' => 'MauticMapperBundle:Client:index',
        'page'        => 1,
    ), array(
        'page' => '\d+'
    )
));

$collection->add('mautic_mapper_client_action', new Route('/mapper/client/{application}/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticMapperBundle:Client:executeClient',
        'objectId'    => 0
    )
));

$collection->add('mautic_mapper_client_object_index', new Route('/mapper/objects/{application}/{client}',
    array('_controller' => 'MauticMapperBundle:Mapper:index')
));

$collection->add('mautic_mapper_client_object_assign', new Route('/mapper/objects/{application}/{client}/{object}',
    array('_controller' => 'MauticMapperBundle:Mapper:index')
));

$collection->add('mautic_mapper_authentication_callback', new Route('/mapper/oauth2callback/{application}',
    array('_controller' => 'MauticMapperBundle:Client:oAuth2Callback')
));

return $collection;
