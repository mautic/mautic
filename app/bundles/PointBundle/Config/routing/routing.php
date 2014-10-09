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

$collection->add('mautic_pointtriggerevent_action', new Route('/points/triggers/events/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticPointBundle:TriggerEvent:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_pointtrigger_index', new Route('/points/triggers/{page}',
    array(
        '_controller' => 'MauticPointBundle:Trigger:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_pointtrigger_action', new Route('/points/triggers/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticPointBundle:Trigger:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_point_index', new Route('/points/{page}',
    array(
        '_controller' => 'MauticPointBundle:Point:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_point_action', new Route('/points/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticPointBundle:Point:execute',
        "objectId"    => 0
    )
));

return $collection;
