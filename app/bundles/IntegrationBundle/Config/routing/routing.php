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


$collection->add('mautic_integration_oauth_callback', new Route('/integrations/connectors/oauth2callback/{connector}',
    array('_controller' => 'MauticIntegrationBundle:Auth:oAuth2Callback')
));

$collection->add('mautic_integration_oauth_postauth', new Route('/integrations/connectors/oauth2/status',
    array('_controller' => 'MauticIntegrationBundle:Auth:oAuthStatus')
));

$collection->add('mautic_integration_connector_index', new Route('/integrations/connectors',
    array('_controller' => 'MauticIntegrationBundle:Connector:index')
));

$collection->add('mautic_integration_connector_edit', new Route('/integrations/connectors/edit/{name}',
    array('_controller' => 'MauticIntegrationBundle:Connector:edit')
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
