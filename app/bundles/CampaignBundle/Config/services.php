<?php

declare(strict_types=1);

use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CampaignBundle\Executioner\TestInactiveExecutioner;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CampaignBundle\Executioner\TestScheduledExecutioner;
use Mautic\CampaignBundle\Security\Permissions\CampaignPermissions;
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

    $services->load('Mautic\\CampaignBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(ScheduledExecutioner::class)->tag('kernel.reset', ['method' => 'reset']);

    if ('test' === ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod')) {
        $services->set(TestInactiveExecutioner::class)
            ->decorate(InactiveExecutioner::class)
            ->tag('kernel.reset', ['method' => 'reset']);

        $services->set(TestScheduledExecutioner::class)
            ->decorate(ScheduledExecutioner::class)
            ->tag('kernel.reset', ['method' => 'reset']);
    }
    $services->set(CampaignPermissions::class);
};
