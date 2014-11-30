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

$collection->add('mautic_crm_index', new Route('/mapper/dashboard',
    array('_controller' => 'MauticCrmBundle:Dashboard:index')
));

$collection->add('mautic_crm_client_index', new Route('/mapper/client/{application}/{page}',
    array(
        '_controller' => 'MauticCrmBundle:Client:index',
        'page'        => 1,
    ), array(
        'page' => '\d+'
    )
));

$collection->add('mautic_crm_client_action', new Route('/mapper/client/{application}/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticCrmBundle:Client:executeClient',
        'objectId'    => 0
    )
));

$collection->add('mautic_crm_client_objects_index', new Route('/mapper/objects/{application}/{client}',
    array('_controller' => 'MauticCrmBundle:Mapper:index')
));

$collection->add('mautic_crm_client_object_action', new Route('/mapper/objects/{application}/{client}/{object}/{objectAction}',
    array(
        '_controller' => 'MauticCrmBundle:Mapper:executeMapper'
    )
));

$collection->add('mautic_crm_authentication_callback', new Route('/mapper/oauth2callback/{application}/{client}',
    array('_controller' => 'MauticCrmBundle:Client:oAuth2Callback')
));

return $collection;
