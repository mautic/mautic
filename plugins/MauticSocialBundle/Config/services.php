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
    $services->set('mautic.social.helper.campaign', MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper::class);
    $services->set('mautic.social.helper.twitter_command', MauticPlugin\MauticSocialBundle\Helper\TwitterCommandHelper::class);
    $services->set('mautic.integration.facebook', MauticPlugin\MauticSocialBundle\Integration\FacebookIntegration::class);
    $services->set('mautic.integration.foursquare', MauticPlugin\MauticSocialBundle\Integration\FoursquareIntegration::class);
    $services->set('mautic.integration.instagram', MauticPlugin\MauticSocialBundle\Integration\InstagramIntegration::class);
    $services->set('mautic.integration.twitter', MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration::class);

    $services->alias('mautic.social.repository.lead', MauticPlugin\MauticSocialBundle\Entity\LeadRepository::class);
    $services->alias('mautic.social.model.monitoring', MauticPlugin\MauticSocialBundle\Model\MonitoringModel::class);
    $services->alias('mautic.social.model.postcount', MauticPlugin\MauticSocialBundle\Model\PostCountModel::class);
    $services->alias('mautic.social.model.tweet', MauticPlugin\MauticSocialBundle\Model\TweetModel::class);
};
