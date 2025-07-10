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

    $services->alias('mautic.social.model.monitoring', MauticPlugin\MauticSocialBundle\Model\MonitoringModel::class);
    $services->alias('mautic.social.model.postcount', MauticPlugin\MauticSocialBundle\Model\PostCountModel::class);
    $services->alias('mautic.social.model.tweet', MauticPlugin\MauticSocialBundle\Model\TweetModel::class);
};
