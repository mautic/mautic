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

    $services->alias('mautic.plugin.model.plugin', Mautic\PluginBundle\Model\PluginModel::class);
    $services->alias('mautic.plugin.model.integration_entity', Mautic\PluginBundle\Model\IntegrationEntityModel::class);

<<<<<<< HEAD
=======
    $services->set(FormSubscriber::class);
        //->call('setIntegrationHelper', [service('mautic.helper.integration')]);
    $services->set(CampaignSubscriber::class);
        //->call('setIntegrationHelper', [service('mautic.helper.integration')]);
>>>>>>> 82ffbc2098 (remove static from PushToIntegraiton)
    $services->set(Mautic\PluginBundle\Security\Permissions\PluginPermissions::class);
};
