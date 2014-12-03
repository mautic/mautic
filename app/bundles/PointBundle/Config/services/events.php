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

//Mautic event listener
$container->setDefinition(
    'mautic.point.subscriber',
    new Definition(
        'Mautic\PointBundle\EventListener\PointSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.point.leadbundle.subscriber',
    new Definition(
        'Mautic\PointBundle\EventListener\LeadSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');