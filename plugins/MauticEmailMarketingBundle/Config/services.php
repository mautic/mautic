<?php

declare(strict_types=1);

use MauticPlugin\MauticEmailMarketingBundle\Integration\ConstantContactIntegration;
use MauticPlugin\MauticEmailMarketingBundle\Integration\IcontactIntegration;
use MauticPlugin\MauticEmailMarketingBundle\Integration\MailchimpIntegration;
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
    $services->alias('mautic.integration.constantcontact', ConstantContactIntegration::class);
    $services->alias('mautic.integration.icontact', IcontactIntegration::class);
    $services->alias('mautic.integration.mailchimp', MailchimpIntegration::class);
};
