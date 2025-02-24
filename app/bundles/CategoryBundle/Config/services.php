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

    $services->load('Mautic\\CategoryBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CategoryBundle\\Entity\\', '../Entity/*Repository.php');
    $services->alias('mautic.category.model.category', Mautic\CategoryBundle\Model\CategoryModel::class);
    $services->alias('mautic.category.model.contact.action', Mautic\CategoryBundle\Model\ContactActionModel::class);
};
