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

$collection->add('mautic_integration_connector_index', new Route('/integrations/connectors',
    array('_controller' => 'MauticIntegrationBundle:Connector:index')
));

$collection->add('mautic_integration_index', new Route('/integrations/{page}',
    array(
        '_controller' => 'MauticIntegrationBundle:Integration:index',
        'page'        => 1,
    ), array(
        'page' => '\d+'
    )
));

$collection->add('mautic_integration_action', new Route('/integrations/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticIntegrationBundle:Integration:execute',
        "objectId"    => 0
    )
));

return $collection;
