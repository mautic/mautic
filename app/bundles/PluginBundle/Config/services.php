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
        'Helper/oAuthHelper.php',
        'Integration/IntegrationObject.php',
        'Form/Constraint/CanPublish.php',
    ];

    $services->load('Mautic\\PluginBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\PluginBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(Mautic\PluginBundle\Security\Permissions\PluginPermissions::class);
};
