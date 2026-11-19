<?php

declare(strict_types=1);

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\MauticTagManagerBundle\Controller\BatchTagController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
    ];

    $services->load('MauticPlugin\\MauticTagManagerBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\MauticTagManagerBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->alias('mautic.tagmanager.model.tag', MauticPlugin\MauticTagManagerBundle\Model\TagModel::class);
    $services->alias('mautic.tagmanager.repository.tag', MauticPlugin\MauticTagManagerBundle\Entity\TagRepository::class);
    $services->get(BatchTagController::class)
        ->tag('controller.service_arguments');
    $services->alias('mautic.integration.tagmanager', MauticPlugin\MauticTagManagerBundle\Integration\TagManagerIntegration::class);
    $services->alias('mautic.integration.tagmanager.config', MauticPlugin\MauticTagManagerBundle\Integration\Support\ConfigSupport::class);
};
