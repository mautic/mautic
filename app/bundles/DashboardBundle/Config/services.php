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
    ];

    $services->load('Mautic\\DashboardBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\DashboardBundle\\Entity\\', '../Entity/*Repository.php');
    $services->set('mautic.dashboard.widget', Mautic\DashboardBundle\Dashboard\Widget::class);
    $services->alias(Mautic\DashboardBundle\Dashboard\Widget::class, 'mautic.dashboard.widget');
    $services->alias('mautic.dashboard.model.dashboard', Mautic\DashboardBundle\Model\DashboardModel::class);
};
