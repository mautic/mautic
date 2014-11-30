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


$collection->add('mautic_integration_oauth_callback', new Route('/addons/integrations/oauth2callback/{integration}',
    array('_controller' => 'MauticAddonBundle:Auth:oAuth2Callback')
));

$collection->add('mautic_integration_oauth_postauth', new Route('/addons/integrations/oauth2/status',
    array('_controller' => 'MauticAddonBundle:Auth:oAuthStatus')
));

$collection->add('mautic_integration_integration_index', new Route('/addons/integrations',
    array('_controller' => 'MauticAddonBundle:Integration:index')
));

$collection->add('mautic_integration_integration_edit', new Route('/addons/integrations/edit/{name}',
    array('_controller' => 'MauticAddonBundle:Integration:edit')
));

$collection->add('mautic_integration_index', new Route('/addons/{page}',
    array(
        '_controller' => 'MauticAddonBundle:Addon:index',
        'page'        => 1,
    ), array(
        'page' => '\d+'
    )
));

$collection->add('mautic_integration_action', new Route('/addons/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticAddonBundle:Addon:execute',
        "objectId"    => 0
    )
));

return $collection;
