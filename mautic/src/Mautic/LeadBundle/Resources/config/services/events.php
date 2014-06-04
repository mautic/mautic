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
    'mautic.lead.subscriber',
    new Definition(
        'Mautic\LeadBundle\EventListener\LeadSubscriber',
        array(
            new Reference('templating'),
            new Reference('request_stack'),
            new Reference('jms_serializer'),
            new Reference('mautic.security'),
            new Reference('translator'),
            new Reference('event_dispatcher'),
            new Reference('mautic.factory'),
            '%mautic.parameters%'
        )
    )
)
    ->addTag('kernel.event_subscriber');