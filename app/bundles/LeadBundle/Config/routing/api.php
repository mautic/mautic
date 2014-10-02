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

$collection->add('mautic_api_getleads', new Route('/leads.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getEntities',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml'
    )
));

$collection->add('mautic_api_newlead', new Route('/leads/new.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'POST',
        '_format' => 'json|xml'
    )
));

$collection->add('mautic_api_getlead', new Route('/leads/{id}.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_editputlead', new Route('/leads/{id}/edit.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'PUT',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_editpatchlead', new Route('/leads/{id}/edit.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'PATCH',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_deletelead', new Route('/leads/{id}/delete.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'DELETE',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_getleadnotes', new Route('/leads/{id}/notes.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getNotes',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

$collection->add('mautic_api_getleadowners', new Route('/leads/list/owners.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getOwners',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml'
    )
));

$collection->add('mautic_api_getleadlists', new Route('/leads/list/lists.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getLists',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml'
    )
));

$collection->add('mautic_api_getleadfields', new Route('/leads/list/fields.{_format}',
    array(
        '_controller' => 'MauticLeadBundle:Api\LeadApi:getFields',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml'
    )
));

return $collection;