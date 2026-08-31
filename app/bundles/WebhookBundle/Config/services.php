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
        'Form/DataTransformer/EventsToArrayTransformer.php',
    ];

    $services->load('Mautic\\WebhookBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\WebhookBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->set('mautic.webhook.campaign.helper', Mautic\WebhookBundle\Helper\CampaignHelper::class)
        ->arg('$client', service('mautic.http.client'));
    $services->alias(Mautic\WebhookBundle\Helper\CampaignHelper::class, 'mautic.webhook.campaign.helper');

    $services->alias('mautic.webhook.model.webhook', Mautic\WebhookBundle\Model\WebhookModel::class);
    $services->alias('mautic.webhook.repository.queue', Mautic\WebhookBundle\Entity\WebhookQueueRepository::class);
};
