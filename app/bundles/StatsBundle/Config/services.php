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
        'Aggregate/Collection',
        'Aggregate/Calculator.php',
    ];

    $services->load('Mautic\\StatsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    $services->set('mautic.stats.aggregate.collector', Mautic\StatsBundle\Aggregate\Collector::class);
    $services->alias(Mautic\StatsBundle\Aggregate\Collector::class, 'mautic.stats.aggregate.collector');
};
