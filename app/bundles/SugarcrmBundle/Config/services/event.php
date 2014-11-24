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

//Mapper event listener
$container->setDefinition(
    'mautic.sugarcrm.mapper.event_listener',
    new Definition('Mautic\SugarcrmBundle\EventListener\MapperListener',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_listener', array(
        'event'  => 'mapper.on_fetch_icons',
        'method' => 'onFetchIcons'
    ))
    ->addTag('kernel.event_listener', array(
        'event'  => 'mapper.on_client_form_build',
        'method' => 'onClientFormBuild'
    ))
    ->addTag('kernel.event_listener', array(
        'event'  => 'mapper.on_object_form_build',
        'method' => 'onObjectFormBuild'
    ));


//Mapper Subscriber
$container->setDefinition(
    'mautic.mapper.subscriber',
    new Definition(
        'Mautic\MapperBundle\EventListener\MapperSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

