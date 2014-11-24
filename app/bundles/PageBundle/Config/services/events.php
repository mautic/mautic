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

//Mautic event listener
$container->setDefinition(
    'mautic.page.subscriber',
    new Definition(
        'Mautic\PageBundle\EventListener\PageSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.pagebuilder.subscriber',
    new Definition(
        'Mautic\PageBundle\EventListener\BuilderSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.page.pointbundle.subscriber',
    new Definition(
        'Mautic\PageBundle\EventListener\PointSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.page.reportbundle.subscriber',
    new Definition(
        'Mautic\PageBundle\EventListener\ReportSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.page.campaignbundle.subscriber',
    new Definition(
        'Mautic\PageBundle\EventListener\CampaignSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.page.leadbundle.subscriber',
    new Definition(
        'Mautic\PageBundle\EventListener\LeadSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');