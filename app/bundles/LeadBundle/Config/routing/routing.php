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

$collection->add('mautic_leadlist_index', new Route('/leads/lists/{page}',
    array(
        '_controller' => 'MauticLeadBundle:List:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_leadlist_action', new Route('/leads/lists/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticLeadBundle:List:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_leadfield_index', new Route('/leads/fields',
    array('_controller' => 'MauticLeadBundle:Field:index')
));

$collection->add('mautic_leadfield_action', new Route('/leads/fields/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticLeadBundle:Field:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_lead_index', new Route('/leads/{page}',
    array(
        '_controller' => 'MauticLeadBundle:Lead:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_leadtimeline_view', new Route('/leads/timeline/{leadId}',
    array(
        '_controller'  => 'MauticLeadBundle:Timeline:view',
        "leadId"       => 0,
    )
));

$collection->add('mautic_lead_action', new Route('/leads/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticLeadBundle:Lead:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_leadnote_index', new Route('/leads/notes/{page}',
    array(
        '_controller' => 'MauticLeadBundle:Note:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_leadnote_action', new Route('/leads/notes/{leadId}/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticLeadBundle:Note:execute',
        "leadId"    => 0,
        "objectId"    => 0
    )
));

return $collection;
