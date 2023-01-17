<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Controller', // Enabling this will require to refactor all controllers to use DI.
        'Generator',
        'Builder/MauticReportBuilder.php',
        'Scheduler/Entity',
        'Scheduler/Option',
    ];

    $services->load('Mautic\\ReportBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\ReportBundle\\Entity\\', '../Entity/*Repository.php');
};
