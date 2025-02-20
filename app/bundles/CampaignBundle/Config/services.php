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

    $services->load('Mautic\\CampaignBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->alias('mautic.campaign.model.campaign', Mautic\CampaignBundle\Model\CampaignModel::class);
    $services->alias('mautic.campaign.model.event', Mautic\CampaignBundle\Model\EventModel::class);
    $services->alias('mautic.campaign.model.event_log', Mautic\CampaignBundle\Model\EventLogModel::class);
    $services->alias('mautic.campaign.model.summary', Mautic\CampaignBundle\Model\SummaryModel::class);
    $services->alias('mautic.campaign.repository.campaign', Mautic\CampaignBundle\Entity\CampaignRepository::class);
    $services->alias('mautic.campaign.repository.lead', Mautic\CampaignBundle\Entity\LeadRepository::class);
    $services->alias('mautic.campaign.repository.event', Mautic\CampaignBundle\Entity\EventRepository::class);
    $services->alias('mautic.campaign.repository.lead_event_log', Mautic\CampaignBundle\Entity\LeadEventLogRepository::class);
    $services->alias('mautic.campaign.repository.summary', Mautic\CampaignBundle\Entity\SummaryRepository::class);
    $services->alias('mautic.campaign.executioner.inactive', Mautic\CampaignBundle\Executioner\InactiveExecutioner::class);
    $services->alias('mautic.campaign.executioner.scheduled', Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class);
    $services->alias('mautic.campaign.scheduler.optimized', Mautic\CampaignBundle\Executioner\Scheduler\Mode\Optimized::class);
    $services->set(Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class)->tag('kernel.reset', ['method' => 'reset']);

    if ('test' === ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod')) {
        $services->set(Mautic\CampaignBundle\Executioner\TestInactiveExecutioner::class)
            ->decorate(Mautic\CampaignBundle\Executioner\InactiveExecutioner::class)
            ->tag('kernel.reset', ['method' => 'reset']);

        $services->set(Mautic\CampaignBundle\Executioner\TestScheduledExecutioner::class)
            ->decorate(Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class)
            ->tag('kernel.reset', ['method' => 'reset']);
    }
};
