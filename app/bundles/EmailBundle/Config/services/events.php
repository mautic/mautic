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
    'mautic.email.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\EmailSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.emailbuilder.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\BuilderSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.email.campaignbundle.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\CampaignSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.email.formbundle.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\FormSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.email.reportbundle.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\ReportSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.email.leadbundle.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\LeadSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.email.pointbundle.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\PointSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.email.calendarbundle.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\CalendarSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.email.search.subscriber',
    new Definition(
        'Mautic\EmailBundle\EventListener\SearchSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

