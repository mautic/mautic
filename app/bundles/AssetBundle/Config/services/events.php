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
    'mautic.asset.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\AssetSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.pointbundle.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\PointSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.formbundle.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\FormSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.campaignbundle.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\CampaignSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.reportbundle.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\ReportSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.builder.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\BuilderSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.leadbundle.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\LeadSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.pagebundle.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\PageSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.emailbundle.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\EmailSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.configbundle.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\ConfigSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.asset.search.subscriber',
    new Definition(
        'Mautic\AssetBundle\EventListener\SearchSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');
