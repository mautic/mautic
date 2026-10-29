<?php

declare(strict_types=1);

use Mautic\WebhookBundle\Helper\CampaignHelper;
use Mautic\WebhookBundle\Security\Permissions\WebhookPermissions;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
    $services->set(CampaignHelper::class)
        ->arg('$client', service('mautic.http.client'));

    $services->set(WebhookPermissions::class);
};
