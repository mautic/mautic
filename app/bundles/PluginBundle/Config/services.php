<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\PluginBundle\EventListener\CampaignSubscriber;
use Mautic\PluginBundle\EventListener\FormSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Helper/oAuthHelper.php',
        'Integration/IntegrationObject.php',
    ];

    $services->load('Mautic\\PluginBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\PluginBundle\\Entity\\', '../Entity/*Repository.php');

<<<<<<< HEAD
    $services->alias('mautic.plugin.model.plugin', Mautic\PluginBundle\Model\PluginModel::class);
    $services->alias('mautic.plugin.model.integration_entity', Mautic\PluginBundle\Model\IntegrationEntityModel::class);
=======
    $services->alias('mautic.plugin.model.plugin', \Mautic\PluginBundle\Model\PluginModel::class);
    $services->alias('mautic.plugin.model.integration_entity', \Mautic\PluginBundle\Model\IntegrationEntityModel::class);
<<<<<<< HEAD
>>>>>>> f529872d14 (fix: [DPMMA-2462] integration helper dependency (#13470))
=======
>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4

    $services->set(FormSubscriber::class)
        ->call('setIntegrationHelper', [service('mautic.helper.integration')]);
    $services->set(CampaignSubscriber::class)
        ->call('setIntegrationHelper', [service('mautic.helper.integration')]);
};
