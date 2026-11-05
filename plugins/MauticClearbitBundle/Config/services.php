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
        'Services',
    ];

    $services->load('MauticPlugin\\MauticClearbitBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->alias('mautic.integration.clearbit', MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration::class);
    $services->alias('mautic.integration.clearbit.config', MauticPlugin\MauticClearbitBundle\Integration\Support\ConfigSupport::class);
    $services->alias('mautic.plugin.clearbit.lookup_helper', MauticPlugin\MauticClearbitBundle\Helper\LookupHelper::class)
        ->deprecate('mautic/mautic', '7.2', 'The "%alias_id%" service alias is deprecated. Use the "'.MauticPlugin\MauticClearbitBundle\Helper\LookupHelper::class.'" service instead.');
};
