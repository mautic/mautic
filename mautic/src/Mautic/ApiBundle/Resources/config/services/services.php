<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

//OAuth Event Listener
$container->setDefinition(
    'mautic.api.oauth.event_listener',
    new Definition('Mautic\ApiBundle\EventListener\OAuthEventListener',
        array(
            new Reference('doctrine.orm.entity_manager'),
            new Reference('mautic.security'),
            new Reference('translator')
        )
    )
)
    ->addTag('kernel.event_listener', array(
        'event'  => 'fos_oauth_server.pre_authorization_process',
        'method' => 'onPreAuthorizationProcess'
    ))
    ->addTag('kernel.event_listener', array(
        'event'  => 'fos_oauth_server.post_authorization_process',
        'method' => 'onPostAuthorizationProcess'
    ));


//API Route Loader
$container->setDefinition ('mautic.api_route_loader',
    new Definition(
        'Mautic\ApiBundle\Routing\RouteLoader',
        array(
            new Reference('service_container')
        )
    )
)
    ->addTag('routing.loader');