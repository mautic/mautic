<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set('oneup_uploader.controller.dropzone.class', Mautic\AssetBundle\Controller\UploadController::class);

    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Controller/UploadController.php',
    ];

    $services->load('Mautic\\AssetBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\AssetBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->set('mautic.asset.fixture.asset', Mautic\AssetBundle\DataFixtures\ORM\LoadAssetData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\AssetBundle\DataFixtures\ORM\LoadAssetData::class, 'mautic.asset.fixture.asset');
    $services->set('mautic.asset.permissions', Mautic\AssetBundle\Security\Permissions\AssetPermissions::class);
    $services->alias(Mautic\AssetBundle\Security\Permissions\AssetPermissions::class, 'mautic.asset.permissions');
    $services->set('mautic.asset.upload.error.handler', Mautic\AssetBundle\ErrorHandler\DropzoneErrorHandler::class);
    $services->alias(Mautic\AssetBundle\ErrorHandler\DropzoneErrorHandler::class, 'mautic.asset.upload.error.handler');
    $services->alias('mautic.asset.helper.token', Mautic\AssetBundle\Helper\TokenHelper::class);
    $services->alias('mautic.asset.model.asset', Mautic\AssetBundle\Model\AssetModel::class);
    $services->alias(Oneup\UploaderBundle\Templating\Helper\UploaderHelper::class, 'oneup_uploader.templating.uploader_helper');
    $services->alias('mautic.asset.repository.download', Mautic\AssetBundle\Entity\DownloadRepository::class);
};
