<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
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

    $services->load('Mautic\\NotificationBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->set(Mautic\NotificationBundle\EventListener\CampaignSubscriber::class)
        ->arg('$notificationApi', service('mautic.notification.api'));
    $services->alias('mautic.integration.onesignal', Mautic\NotificationBundle\Integration\OneSignalIntegration::class);

    $services->alias('mautic.notification.model.notification', Mautic\NotificationBundle\Model\NotificationModel::class);

    $services->alias('mautic.notification.api', Mautic\NotificationBundle\Api\OneSignalApi::class);
};
