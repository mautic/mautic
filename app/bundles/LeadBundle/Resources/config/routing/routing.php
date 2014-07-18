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

$collection->add('mautic_lead_action', new Route('/leads/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticLeadBundle:Lead:execute',
        "objectId"    => 0
    )
));

//register social media
$collection->add('mautic_leadsocial_index', new Route('/social/config',
    array('_controller' => 'MauticLeadBundle:Social:index')
));

$collection->add('mautic_leadsocial_callback', new Route('/social/{service}/oauth2callback',
    array('_controller' => 'MauticLeadBundle:Social:oAuth2Callback')
));

return $collection;
