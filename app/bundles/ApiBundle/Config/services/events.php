<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

//OAuth event listener
$container->setDefinition(
    'mautic.api.oauth.event_listener',
    new Definition('Mautic\ApiBundle\EventListener\OAuthEventListener',
        array(new Reference('mautic.factory'))
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

//Mautic event listener
$container->setDefinition(
    'mautic.api.subscriber',
    new Definition(
        'Mautic\ApiBundle\EventListener\ApiSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.api.configbundle.subscriber',
    new Definition(
        'Mautic\ApiBundle\EventListener\ConfigSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.api.search.subscriber',
    new Definition(
        'Mautic\ApiBundle\EventListener\SearchSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

