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

    $services->load('Mautic\\IntegrationsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\IntegrationsBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->alias('mautic.integrations.repository.field_change', Mautic\IntegrationsBundle\Entity\FieldChangeRepository::class);
    $services->alias('mautic.integrations.repository.object_mapping', Mautic\IntegrationsBundle\Entity\ObjectMappingRepository::class);
    $services->alias('mautic.plugin.integrations.repository.integration', Mautic\PluginBundle\Entity\IntegrationRepository::class);
    $services->alias('mautic.integrations.helper.contact_object', Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper::class);
    $services->alias('mautic.integrations.helper.company_object', Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper::class);
    $services->alias('mautic.integrations.sync.data_exchange.mautic.field_helper', Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper::class);
    $services->alias('mautic.integrations.helper.sync_mapping', Mautic\IntegrationsBundle\Sync\Helper\MappingHelper::class);
};
