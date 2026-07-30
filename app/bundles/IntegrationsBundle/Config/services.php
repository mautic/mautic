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
        'Helper/FieldFilterHelper.php',
        'Helper/FieldMergerHelper.php',
        'Auth/Support/Oauth2/Token',
        'Sync/DAO',
        'Sync/Exception',
        'Sync/SyncDataExchange/Internal/Executioner/Exception',
        'Sync/SyncProcess/SyncProcess.php',
        'Integration/IntegrationObject.php',
    ];

    $services->set(Mautic\IntegrationsBundle\Sync\SyncService\SyncService::class)
        ->call('initiateDebugLogger', [\Symfony\Component\DependencyInjection\Loader\Configurator\service('mautic.sync.logger')]);

    $services->load('Mautic\\IntegrationsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\IntegrationsBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->set(Mautic\IntegrationsBundle\EventListener\ControllerSubscriber::class)
        ->arg('$resolver', \Symfony\Component\DependencyInjection\Loader\Configurator\service('controller_resolver'));

    $services->set(Mautic\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelper::class);
    $services->set(Mautic\IntegrationsBundle\Helper\FieldValidationHelper::class);
    $services->set(Mautic\IntegrationsBundle\Facade\EncryptionService::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Helper\OwnerProvider::class);
    $services->set(Mautic\IntegrationsBundle\Auth\Provider\ApiKey\HttpFactory::class);
    $services->set(Mautic\IntegrationsBundle\Auth\Provider\BasicAuth\HttpFactory::class);
    $services->set(Mautic\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged\HttpFactory::class);
    $services->set(Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\HttpFactory::class);
    $services->set('mautic.integrations.auth_provider.oauth2threelegged', Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\HttpFactory::class);
    $services->set(Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\TokenPersistenceFactory::class);
    $services->set(Mautic\IntegrationsBundle\Helper\TokenParser::class);
    $services->set('mautic.sync.logger', Mautic\IntegrationsBundle\Sync\Logger\DebugLogger::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudge::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\OrderExecutioner::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\FieldValidator::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\ReferenceResolver::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Helper\ValueHelper::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FieldBuilder::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FullObjectReportBuilder::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\PartialObjectReportBuilder::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\ObjectChangeGenerator::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\IntegrationSyncProcess::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator::class);
    $services->set(Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\MauticSyncProcess::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Helper\RelationsHelper::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Notifier::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Writer::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Handler\CompanyNotificationHandler::class)->tag('mautic.sync.notification_handler');
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Handler\ContactNotificationHandler::class)->tag('mautic.sync.notification_handler');
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Helper\CompanyHelper::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Helper\UserHelper::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Helper\RouteHelper::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationBuilder::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\BulkNotification::class);
    $services->set(Mautic\IntegrationsBundle\Sync\Notification\Helper\UserSummaryNotificationHelper::class);

    $services->alias('mautic.integrations.helper', Mautic\IntegrationsBundle\Helper\IntegrationsHelper::class);
    $services->alias('mautic.integrations.helper.auth_integrations', Mautic\IntegrationsBundle\Helper\AuthIntegrationsHelper::class);
    $services->alias('mautic.integrations.helper.sync_integrations', Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper::class);
    $services->alias('mautic.integrations.helper.config_integrations', Mautic\IntegrationsBundle\Helper\ConfigIntegrationsHelper::class);
    $services->alias('mautic.integrations.helper.builder_integrations', Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper::class);
    $services->alias('mautic.integrations.sync.notification.handler_container', Mautic\IntegrationsBundle\Sync\Notification\Handler\HandlerContainer::class);
};
