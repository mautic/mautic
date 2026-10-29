<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\NotificationBundle\Api\OneSignalApi;
use Mautic\NotificationBundle\EventListener\CampaignSubscriber;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;
use Mautic\NotificationBundle\Security\Permissions\NotificationPermissions;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [];

    $services->load('Mautic\\NotificationBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\NotificationBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(CampaignSubscriber::class)
        ->arg('$notificationApi', service(OneSignalApi::class));

    // resolved dynamically by Mautic\PluginBundle\Helper\IntegrationHelper via "mautic.integration.{name}" service id
    $services->alias('mautic.integration.onesignal', OneSignalIntegration::class);

    $services->set(NotificationPermissions::class);
};
