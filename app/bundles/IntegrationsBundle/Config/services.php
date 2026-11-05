<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\HttpFactory;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\TokenPersistenceFactory;
use Mautic\IntegrationsBundle\EventListener\ControllerSubscriber;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\CompanyNotificationHandler;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\ContactNotificationHandler;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Helper/FieldFilterHelper.php',
        'Helper/FieldMergerHelper.php',
        'Auth/Support/Oauth2/Token',
        'Sync/DAO',
        'Sync/Exception',
        'Sync/SyncDataExchange/Internal/Executioner/Exception',
        'Sync/SyncProcess/SyncProcess.php',
        'Integration/IntegrationObject.php',
    ];

    $services->set(SyncService::class)
        ->call('initiateDebugLogger', [service('mautic.sync.logger')]);

    $services->load('Mautic\\IntegrationsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\IntegrationsBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(ControllerSubscriber::class)
        ->arg('$resolver', service('controller_resolver'));

    $services->set('mautic.integrations.auth_provider.oauth2threelegged', HttpFactory::class);
    $services->set(TokenPersistenceFactory::class);
    $services->set('mautic.sync.logger', DebugLogger::class);
    $services->set(CompanyNotificationHandler::class)->tag('mautic.sync.notification_handler');
    $services->set(ContactNotificationHandler::class)->tag('mautic.sync.notification_handler');
};
