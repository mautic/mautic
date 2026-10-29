<?php

declare(strict_types=1);

use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\DynamicContentBundle\Security\Permissions\DynamicContentPermissions;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('Mautic\\DynamicContentBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->load('Mautic\\DynamicContentBundle\\Entity\\', '../Entity/*Repository.php');
    $services->alias('mautic.dynamicContent.model.dynamicContent', DynamicContentModel::class);
    $services->set(DynamicContentPermissions::class);
};
