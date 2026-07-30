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
        'Api',
    ];

    $services->load('MauticPlugin\\MauticEmailMarketingBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    $services->set(MauticPlugin\MauticEmailMarketingBundle\Integration\ConstantContactIntegration::class);
    $services->alias('mautic.integration.constantcontact', MauticPlugin\MauticEmailMarketingBundle\Integration\ConstantContactIntegration::class);
    $services->set(MauticPlugin\MauticEmailMarketingBundle\Integration\IcontactIntegration::class);
    $services->alias('mautic.integration.icontact', MauticPlugin\MauticEmailMarketingBundle\Integration\IcontactIntegration::class);
    $services->set(MauticPlugin\MauticEmailMarketingBundle\Integration\MailchimpIntegration::class);
    $services->alias('mautic.integration.mailchimp', MauticPlugin\MauticEmailMarketingBundle\Integration\MailchimpIntegration::class);
};
