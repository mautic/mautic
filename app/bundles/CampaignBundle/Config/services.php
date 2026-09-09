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
        'EventCollector/Accessor',
        'Executioner/ContactFinder/Limiter/ContactLimiter.php',
        'Executioner/Dispatcher/Exception',
        'Executioner/Scheduler/Mode/DAO',
        'Membership/Exception',
    ];

    $services->load('Mautic\\CampaignBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CampaignBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->set(Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class)->tag('kernel.reset', ['method' => 'reset']);

    if ('test' === ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod')) {
        $services->set(Mautic\CampaignBundle\Executioner\TestInactiveExecutioner::class)
            ->decorate(Mautic\CampaignBundle\Executioner\InactiveExecutioner::class)
            ->tag('kernel.reset', ['method' => 'reset']);

        $services->set(Mautic\CampaignBundle\Executioner\TestScheduledExecutioner::class)
            ->decorate(Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class)
            ->tag('kernel.reset', ['method' => 'reset']);
    }
    $services->set(Mautic\CampaignBundle\Security\Permissions\CampaignPermissions::class);
};
