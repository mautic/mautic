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
    ];

    $services->load('MauticPlugin\\MauticSocialBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\MauticSocialBundle\\Entity\\', '../Entity/*Repository.php');
    $services->set('mautic.integration.facebook', MauticPlugin\MauticSocialBundle\Integration\FacebookIntegration::class);
    $services->set('mautic.integration.foursquare', MauticPlugin\MauticSocialBundle\Integration\FoursquareIntegration::class);
    $services->set('mautic.integration.instagram', MauticPlugin\MauticSocialBundle\Integration\InstagramIntegration::class);
    $services->set('mautic.integration.twitter', MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration::class);

    $services->set(MauticPlugin\MauticSocialBundle\Security\Permissions\MauticSocialPermissions::class);
};
