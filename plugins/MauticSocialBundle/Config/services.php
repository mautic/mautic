<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\MauticSocialBundle\Integration\FacebookIntegration;
use MauticPlugin\MauticSocialBundle\Integration\FoursquareIntegration;
use MauticPlugin\MauticSocialBundle\Integration\InstagramIntegration;
use MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration;
use MauticPlugin\MauticSocialBundle\Security\Permissions\MauticSocialPermissions;
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
    $services->set('mautic.integration.facebook', FacebookIntegration::class);
    $services->set('mautic.integration.foursquare', FoursquareIntegration::class);
    $services->set('mautic.integration.instagram', InstagramIntegration::class);
    $services->set('mautic.integration.twitter', TwitterIntegration::class);

    $services->set(MauticSocialPermissions::class);
};
