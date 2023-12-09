<?php

declare(strict_types=1);
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use MauticPlugin\MauticSocialBundle\Model\PostCountModel;
use MauticPlugin\MauticSocialBundle\Model\TweetModel;
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

    $services->alias('mautic.social.model.monitoring', MonitoringModel::class);
    $services->alias('mautic.social.model.postcount', PostCountModel::class);
    $services->alias('mautic.social.model.tweet', TweetModel::class);
};
