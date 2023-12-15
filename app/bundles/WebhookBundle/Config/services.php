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
        'Form/DataTransformer/EventsToArrayTransformer.php',
    ];

    $services->load('Mautic\\WebhookBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\WebhookBundle\\Entity\\', '../Entity/*Repository.php');

    $services->alias('mautic.webhook.model.webhook', \Mautic\WebhookBundle\Model\WebhookModel::class);
};
