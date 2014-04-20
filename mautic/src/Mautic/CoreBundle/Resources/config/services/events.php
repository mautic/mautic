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
use Symfony\Component\DependencyInjection\Parameter;

//Mautic event listener
$container->setDefinition(
    'mautic.core.subscriber',
    new Definition(
        'Mautic\CoreBundle\EventListener\CoreSubscriber',
        array(new Reference('service_container'))
    )
)
    ->addTag('kernel.event_subscriber');

//Listener to prepopulate information such as bundle name, action name, template name, etc into the request
//attributes for use in the templates
$container->setDefinition(
    'mautic.event_listener',
    new Definition('Mautic\CoreBundle\EventListener\MauticListener')
)
    ->addTag('kernel.event_listener', array(
        'event'  => 'kernel.controller',
        'method' => 'onKernelController'
    ));