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

$collection->add('mautic_api_getleads', new Route('/leads',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getEntities',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_newlead', new Route('/leads/new',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'POST'
    )
));

$collection->add('mautic_api_getlead', new Route('/leads/{id}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_editputlead', new Route('/leads/{id}/edit',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'PUT',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_editpatchlead', new Route('/leads/{id}/edit',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'PATCH',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_deletelead', new Route('/leads/{id}/delete',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'DELETE',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_getleadnotes', new Route('/leads/{id}/notes',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getNotes',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_getleadowners', new Route('/leads/list/owners',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getOwners',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getleadlists', new Route('/leads/list/lists',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getLists',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getleadfields', new Route('/leads/list/fields',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getFields',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

return $collection;