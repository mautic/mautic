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

    $excludes = [
        'Api',
        'Integration/Salesforce',
    ];

    $services->load('MauticPlugin\\MauticCrmBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    $services->set('mautic_integration.service.transport', MauticPlugin\MauticCrmBundle\Services\Transport::class)
        ->arg('$client', service('mautic.http.client'));
    $services->alias(MauticPlugin\MauticCrmBundle\Services\Transport::class, 'mautic_integration.service.transport');

    $services->alias('mautic.integration.hubspot', MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration::class);
    $services->alias('mautic.integration.salesforce', MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration::class);
    $services->alias('mautic.integration.sugarcrm', MauticPlugin\MauticCrmBundle\Integration\SugarcrmIntegration::class);
    $services->alias('mautic.integration.vtiger', MauticPlugin\MauticCrmBundle\Integration\VtigerIntegration::class);
    $services->alias('mautic.integration.zoho', MauticPlugin\MauticCrmBundle\Integration\ZohoIntegration::class);
    $services->alias('mautic.integration.dynamics', MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration::class);
    $services->alias('mautic.integration.connectwise', MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration::class);
};
