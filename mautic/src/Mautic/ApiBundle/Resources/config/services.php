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

//API Client form
$container->setDefinition(
    'mautic.form.type.apiclients',
    new Definition(
        'Mautic\ApiBundle\Form\Type\ApiClientType',
        array(
            new Reference("service_container"),
            new Reference('security.context'),
            "%mautic.bundles%"
        )
    )
)
    ->addTag('form.type', array(
        'alias' => 'apiClient',
    ));

//OAuth Event Listener
$container->setDefinition(
    'mautic.api.oauth.event_listener',
    new Definition('Mautic\ApiBundle\EventListener\OAuthEventListener',
        array(
            new Reference('doctrine.orm.entity_manager'),
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

//Client Model
$container->setDefinition(
    'mautic.model.client',
    new Definition(
        'Mautic\ApiBundle\Model\ClientModel',
        array(
            new Reference("service_container"),
            new Reference('request_stack'),
            new Reference('doctrine.orm.entity_manager'),
        )
    )
);