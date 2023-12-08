<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'EventCollector',
        'Executioner/ContactFinder/Limiter/ContactLimiter.php',
        'Executioner/Dispatcher/Exception',
        'Executioner/Scheduler/Mode/DAO',
        'Membership/Exception',
    ];

    $services->load('Mautic\\CampaignBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CampaignBundle\\Entity\\', '../Entity/*Repository.php');
    $services->alias('mautic.campaign.model.campaign', \Mautic\CampaignBundle\Model\CampaignModel::class);
    $services->alias('mautic.campaign.model.event', \Mautic\CampaignBundle\Model\EventModel::class);
    $services->alias('mautic.campaign.model.event_log', \Mautic\CampaignBundle\Model\EventLogModel::class);
    $services->alias('mautic.campaign.model.summary', \Mautic\CampaignBundle\Model\SummaryModel::class);
};
