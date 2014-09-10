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

$collection->add('mautic_campaignevent_action', new Route('/campaigns/campaigns/events/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticCampaignBundle:CampaignEvent:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_campaign_index', new Route('/campaigns/campaigns/{page}',
    array(
        '_controller' => 'MauticCampaignBundle:Campaign:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_campaign_action', new Route('/campaigns/campaigns/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticCampaignBundle:Campaign:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_campaign_index', new Route('/campaigns/{page}',
    array(
        '_controller' => 'MauticCampaignBundle:Campaign:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_campaign_action', new Route('/campaigns/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticCampaignBundle:Campaign:execute',
        "objectId"    => 0
    )
));

return $collection;
