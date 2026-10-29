<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;
use MauticPlugin\MauticCrmBundle\Integration\SugarcrmIntegration;
use MauticPlugin\MauticCrmBundle\Integration\VtigerIntegration;
use MauticPlugin\MauticCrmBundle\Integration\ZohoIntegration;
use MauticPlugin\MauticCrmBundle\Services\Transport;
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
    $services->set('mautic_integration.service.transport', Transport::class)
        ->arg('$client', service('mautic.http.client'));
    $services->alias(Transport::class, 'mautic_integration.service.transport');

    $services->alias('mautic.integration.hubspot', HubspotIntegration::class);
    $services->alias('mautic.integration.salesforce', SalesforceIntegration::class);
    $services->alias('mautic.integration.sugarcrm', SugarcrmIntegration::class);
    $services->alias('mautic.integration.vtiger', VtigerIntegration::class);
    $services->alias('mautic.integration.zoho', ZohoIntegration::class);
    $services->alias('mautic.integration.dynamics', DynamicsIntegration::class);
    $services->alias('mautic.integration.connectwise', ConnectwiseIntegration::class);
};
