<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\MauticCompanyEnrichBundle\Integration\CompanyEnrichIntegration;
use MauticPlugin\MauticCompanyEnrichBundle\Integration\Support\ConfigSupport;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()->defaults()->autowire()->autoconfigure()->public();
    $services->load('MauticPlugin\\MauticCompanyEnrichBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');
    $services->alias('mautic.integration.companyenrich', CompanyEnrichIntegration::class);
    $services->alias('mautic.integration.companyenrich.config', ConfigSupport::class);
};
